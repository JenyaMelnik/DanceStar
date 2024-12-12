#!/bin/bash

if [ ! -f .env ]; then
  echo "Copying .env.example to .env..."
  cp .env.example .env
  echo ".env file created from .env.example."
else
  echo ".env already exists."
fi
