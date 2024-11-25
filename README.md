# Laravel Application

This Laravel application is developed using Sail. Follow the instructions below to deploy the project locally.

## Requirements

- Docker and Docker Compose must be installed on your system.
- Git to clone the repository.

## Installation

1. **Clone the repository:**

    ```bash
    git clone git@github.com:JenyaMelnik/DanceStar.git
    cd <your project folder>
    ```

2. **Copy `.env.example` to `.env`:**

    ```bash
    cp .env.example .env
    ```

3. **Configure the `.env` file:**

    In the `.env` file, set the following parameters for database connection:

    ```env
    DB_CONNECTION=mysql
    DB_HOST=mysql
    DB_PORT=3306
    DB_DATABASE=your_database_name
    DB_USERNAME=your_username
    DB_PASSWORD=your_password
    ```

    **Note:** Sail automatically creates a `mysql` container, so the host (`DB_HOST`) should be set to `mysql`.

4. **Start Sail and install dependencies:**

    ```bash
    ./vendor/bin/sail up -d
    ./vendor/bin/sail composer install
    ./vendor/bin/sail npm install
    ```

5. **Generate the application key:**

    ```bash
    ./vendor/bin/sail artisan key:generate
    ```

6. **Create the database:**

    Open the MySQL container:

    ```bash
    ./vendor/bin/sail mysql
    ```

    Then run the following command to create the database (if it doesn't already exist):

    ```sql
    CREATE DATABASE your_database_name;
    ```

7. **Run migrations:**

    ```bash
    ./vendor/bin/sail artisan migrate
    ```

## Running the Application

1. **Start the application:**

    ```bash
    ./vendor/bin/sail up
    ```

2. Open [http://localhost](http://localhost) in your browser to view the application.

## Useful Commands

- **Stop Sail:**

    ```bash
    ./vendor/bin/sail down
    ```

- **Clear the cache:**

    ```bash
    ./vendor/bin/sail artisan cache:clear
    ```

- **Interact with the database using Tinker:**

    ```bash
    ./vendor/bin/sail artisan tinker
    ```