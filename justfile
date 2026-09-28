# inanepain/session
# version: $Id$
# date: $Date$

set shell := ["zsh", "-cu"]
set positional-arguments

project := "inane\\session"

# list recipes
_default:
    @echo "{{project}}:"
    @just --list --list-heading ''

# start
_start task='':
    @echo "{{project}}: {{GREEN}}start{{NORMAL}}: {{task}}"

# done
_done task='':
    @echo "{{project}}: {{GREEN}}done{{NORMAL}} {{task}}"

# git push all
[group: 'GIT']
git-push-all: (_start "Push All") && (_done "Push All")
    #!/usr/bin/env zsh
    git pushall

# generate php part (v2) (all, cache, html)
php-doc clear="all":
	#!/usr/bin/env zsh
	if [ -d .phpdoc ] && [[ "{{clear}}" = "all" || "{{clear}}" = "cache" ]]; then
		echo "\tCleaning: cache..."
		rm -fr .phpdoc
	fi
	if [ -d phpdoc ] && [[ "{{clear}}" = "all" || "{{clear}}" = "html" ]]; then
		echo "\tCleaning: html..."
		rm -fr phpdoc
	fi

	mkdir -p phpdoc
	phpdoc -d src -t phpdoc --title="{{project}}" --defaultpackagename="Inane"
