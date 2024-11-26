# Laravel Application

This Laravel application is developed using Sail. Follow the instructions below to deploy the project locally.

## Requirements

- Docker and Docker Compose must be installed on your system.
- Git to clone the repository.

## Installation

1. **Clone the repository:**

    ```bash
    git clone git@github.com:JenyaMelnik/DanceStar.git
    ```
    ```bash
    cd DanceStar
    ```


2. **Execute the ‘make’ command to deploy the project:**

    ```bash
    make deploy
    ```

3. **On most systems, ‘make’ is pre-installed. To check this, run:**

    ```bash
    make --version
    ```
4.  **If the command is not available, install ‘make’:<br>**
    **For Ubuntu/Debian:**

    ```bash
    sudo apt update && sudo apt install make
    ```

    **For MacOS:**

    ```bash
    xcode-select --install
    ```

    **On Windows: Install ‘Make’ for Windows or use WSL.**


5. Open [http://localhost](http://localhost) in your browser to view the application.

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