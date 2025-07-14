# выполнить миграции
.PHONY: migrate
migrate:
	docker exec -it gsoft_php82 bash -c 'cd /var/www/TestTasks/$(shell basename $(CURDIR))/; php artisan migrate'

# откатить миграции
.PHONY: rollback
rollback:
	docker exec -it gsoft_php82 bash -c 'cd /var/www/TestTasks/$(shell basename $(CURDIR))/; php artisan migrate:rollback'

# установить новые модули из composer
.PHONY: composer_install
composer_install:
	docker exec -it gsoft_php82 bash -c 'cd /var/www/TestTasks/$(shell basename $(CURDIR))/; composer install'

# artisan
.PHONY: artisan
artisan:
	docker exec -it gsoft_php82 bash -c 'cd /var/www/TestTasks/$(shell basename $(CURDIR))/; php artisan $(filter-out $@,$(MAKECMDGOALS))'


# выполнить произвольную команду в контейнере
.PHONY: command
command:
	docker exec -it gsoft_php82 bash -c 'cd /var/www/TestTasks/$(shell basename $(CURDIR))/; $(filter-out $@,$(MAKECMDGOALS))'

