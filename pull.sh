#!/bin/bash
# Залитие изменений git на текущей ветке

echo -e "\033[0;32m$(pwd)\033[0m"
git pull --rebase
