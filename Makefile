.PHONY: foreman invoker inject filters

foreman:
	bundle exec foreman start

invoker:
	bundle exec invoker start Procfile

inject:
	php examples/injector.php

filters:
	php examples/filters.php
