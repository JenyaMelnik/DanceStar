ABSOLUTE_PATH=$(realpath ./.docker/mysql/create-testing-database.sh)

if [ "$(uname)" = "Darwin" ]; then
    sed -i '' "s|RELATIVE_PATH|$ABSOLUTE_PATH|g" docker-compose.yml
else
    sed -i "s|RELATIVE_PATH|$ABSOLUTE_PATH|g" docker-compose.yml
fi
