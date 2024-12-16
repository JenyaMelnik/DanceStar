#!/bin/bash

if [ ! -f .env ]; then
  echo "Copying .env.example to .env..."
  cp .env.example .env

  # Get UID and GID of the current user
  USER_ID=$(id -u)
  GROUP_ID=$(id -g)

  # Add USER and GROUP to .env
  echo "WWWUSER=$USER_ID" >> .env
  echo "WWWGROUP=$GROUP_ID" >> .env

  echo ".env file created from .env.example."
else
  echo ".env already exists."
fi
