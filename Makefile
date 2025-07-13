# MD WORKFLOW
# Make some automation

############# TESTING TASKS #############

test_md_make_fixtures:
	# создание тестовой базы данных
	# выполняется с помощью вызова логики приложения
	# (тут говорят, что это может быть плохо и тяжело при долгой разработке, но мне не верится)
	# эту подготовку не обязательно делать каждый раз перед прогоном тестов, достаточно делать
	# только тогда, когда меняется логика работы с базой данных
	# в результате должен создаваться дамп БД, который должен использоваться для создания БД перед прогоном тестов

    #  пересоздаем БД ???
	docker exec -i gsoft_mysql mysql -u root -proot -e "drop database if exists mdworkflow_test_fixtures"
	docker exec -i gsoft_mysql mysql -u root -proot -e "create database mdworkflow_test_fixtures"

    # актуализируем структуру БД запуском миграций
	php ~/www/mdworkflow/artisan migrate --env=testing

    # наполняем бд тестовыми данными с помощью бизнес-логики приложения
	php ~/www/mdworkflow/artisan make:fixtures --env=fixtures

    # выгружаем данные из бд в sql-dump файл ./database/fixtures/test_db_dump.sql
	#docker exec -i gsoft_mysql mysqldump -u root -proot ?????


test_init_db: test_md_make_fixtures
    # после выполнения test_make_fixtures у нас есть слепок тестовой БД, который нужно исползьовать
    # для этого нужно договориться, где будет лежать этот слепок, пусть это будет сейчас:
    # ./database/fixtures/test_db_dump.sql
    # в тестах. для этого МОЖНО (но это пока слишком) перед прогоном проверять, не поменялся ли слепок после последнего создания тестовой БД

    # восстанавливаем БД из бэкапа, предварительно удалив и создав базу данных
	docker exec -i gsoft_mysql mysql -u root -proot -e "drop database if exists mdworkflow_test"
	docker exec -i gsoft_mysql mysql -u root -proot -e "create database mdworkflow_test"
	docker exec -i gsoft_mysql mysql -u root -proot mdworkflow_test < ~/www/mdworkflow/database/fixtures/test_db_dump.sql


test: test_init_db test_es_start
	./vendor/bin/phpunit

test_visual:
	./vendor/bin/phpunit --testdox

testone:
    # запуск одного теста
	./vendor/bin/phpunit  --filter  testCreateProcessType tests/Feature/ProcessTypeTest.php

test_es_start:
	# задача выполняется для системы ES, т.к. в процессе интеграционного тестирования
	# MD Workflow нужно использовать синхронизированную с ней (тестовую) БД и в ES
	# для этого мы переключаем .env, используемый в ES на время тестирования
	# (не лучший способ, тк на это время нельзя будет пользоваться MD из-за того, что на ES переключена БД
	# и из-за того, что в конце нужно переключить БД обратно и если прервать тесты - придется переключать вручную
	# но лучшего метода указать ES, что данный запрос по REST API идет для тестов я пока не придумал
	# придумал - можно во всех запросах добавлять параметр testing=true,
	# но как на него реагировать в ES - переключать БД на тестовую - не придумал

	# придумал вариант лучше! создать отдельную копию сайта ES для тестирования и
	# перед запуском тестов обновлять эту версию (CI/CD) и фикстуры в ней
	# всегда использовать в этой копии тестовую среду - .env.testing


    # перед этой командой, не всегда: создать фикстуры, выложить их в sql, пересоздать тестовую БД, залить в неё фикстуры
	# make test_es_make_fixtures
	make test_es_init_db
    # запустить delivery для ES тестового сайта


test_es_make_fixtures:
	# создание тестовой базы данных
	# выполняется с помощью вызова логики приложения
	# (тут говорят, что это может быть плохо и тяжело при долгой разработке, но мне не верится)
	# эту подготовку не обязательно делать каждый раз перед прогоном тестов, достаточно делать
	# только тогда, когда меняется логика работы с базой данных
	# в результате должен создаваться дамп БД, который должен использоваться для создания БД перед прогоном тестов

    # пересоздаем БД ???
	docker exec -i gsoft_mysql mysql -u root -proot -e "drop database if exists entity_studio_test_fixtures"
	docker exec -i gsoft_mysql mysql -u root -proot -e "create database entity_studio_test_fixtures"

    # актуализируем структуру БД запуском миграций
	docker exec -i gsoft_php82 php /var/www/TestTasks/entity_studio_test/artisan migrate --env=testing


    # наполняем бд тестовыми данными с помощью бизнес-логики приложения
	# docker exec -i gsoft_php82 php /var/www/TestTasks/entity_studio/artisan make:fixtures --env=fixtures
	# здесь это не нужно, т.к. наполняться будет из mdworkflow. А если нужно тестировать только ES,
	# то это нужно создать в проекте ES

    # выгружаем данные из бд в sql-dump файл ./database/fixtures/test_db_dump.sql
	#docker exec -i gsoft_mysql mysqldump -u root -proot ?????


test_es_init_db:
    # после выполнения test_make_fixtures у нас есть слепок тестовой БД, который нужно использовать
    # для этого нужно договориться, где будет лежать этот слепок, пусть это будет сейчас:
    # ./database/fixtures/test_db_dump.sql
    # в тестах. для этого МОЖНО (но это пока слишком) перед прогоном проверять, не поменялся ли слепок после последнего создания тестовой БД

    # восстанавливаем БД из бэкапа, предварительно удалив и создав базу данных
	docker exec -i gsoft_mysql mysql -u root -proot -e "drop database if exists entity_studio_test"
	docker exec -i gsoft_mysql mysql -u root -proot -e "create database entity_studio_test"
	docker exec -i gsoft_mysql mysql -u root -proot entity_studio_test < ~/www/entity_studio_test/database/fixtures/test_db_dump.sql



############# DATA RESET #############

datareset:
	#удаляем все таблицы
	php artisan droptables
	#запускаем миграции (seeds лучше не использовать для системных данных, т.к. эта команда не идемпотентная)
	php artisan migrate


############# CI/CD/DEPLOY #############
# deploy - развертывание сайта на новом виртуалхосте на настроенном docker-сервере baltun7.ru
deploy:
	/Users/ilya/ansible/ansible_playbook create_play.yml

# удаление развернутого сайта
deploy_delete:
	/Users/ilya/ansible/ansible_playbook delete_play.yml ???

# CI/CD - обновление после доработок на всех сайтах, только для MD Workflow ?
deliver_staging:
	/Users/ilya/ansible/ansible_playbook ci_cd_play.yml ???

deliver_production:
	/Users/ilya/ansible/ansible_playbook ci_cd_play.yml ???


############# САМООТЛАДКА #############
# проверка работы с make и тп
debug: debug_dependend1 debug_dependend2
	echo 'debug'
debug_dependend1:
	echo 'dependend1'

debug_dependend2:
	echo 'dependend2'

debug_ip:
	php artisan make:fixtures --env=testing


##############   ЛОКАЛЬНЫЕ ЗАДАЧИ   ###############
# выполнить миграции
migrate:
	docker exec -it gsoft_php82 bash -c 'cd /var/www/TestTasks/$(shell basename $(CURDIR))/; php artisan migrate'

# откатить миграции
rollback:
	docker exec -it gsoft_php82 bash -c 'cd /var/www/TestTasks/$(shell basename $(CURDIR))/; php artisan migrate:rollback'

# установить новые модули из composer
composer_install:
	docker exec -it gsoft_php82 bash -c 'cd /var/www/TestTasks/$(shell basename $(CURDIR))/; composer install'

# сделать СТРАШНОЕ обновление из composer
composer_update:
	docker exec -it gsoft_php82 bash -c 'cd /var/www/TestTasks/$(shell basename $(CURDIR))/; composer update'

# сделать бэкап на сервере


# выполнить произвольную команду в контейнере
command:
	docker exec -it gsoft_php82 bash -c 'cd /var/www/TestTasks/$(shell basename $(CURDIR))/; $(filter-out $@,$(MAKECMDGOALS))'


# выполнить один тест
test_run_local:
	./vendor/bin/phpunit $(filter-out $@,$(MAKECMDGOALS))

# выполнить все тесты
tests_run_local:
	./vendor/bin/phpunit --testdox

# выполнить один тест в контейнере
test_run:
	docker exec -it gsoft_php82 bash -c 'cd /var/www/TestTasks/$(shell basename $(CURDIR))/; ./vendor/bin/phpunit $(filter-out $@,$(MAKECMDGOALS))'

# создать новый сайт
site_create:
	sudo bash ./_site_create_new-docker-nginx.sh
