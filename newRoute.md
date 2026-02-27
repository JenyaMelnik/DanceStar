# Создание нового роута

## Общая последовательность создания роута:

1. Создание Eloquent Model (если нет)
2. Создание Repository Interface
3. Создание Repository Implementation (работа с моделью)
4. Создание DTO (передача данных)
5. Создание Service (бизнес-логика с репозиторием)
6. Создание Request (валидация)
7. Создание Resource (форматирование ответа)
8. Создать Invokable Controller (инвокбл, работает с сервисом)
9. Регистрация в Service Provider (DI контейнер)
10. Регистрация роута (routes файл)
11. Добавление middleware (если необходимо)

---

## Общие компоненты для всех HTTP методов:

### 1. Eloquent Model (если нет)
    В нашем случае модель User уже есть.

### 2. Repository Interface
```php
<?php
// app/Domains/User/Repositories/UserRepositoryInterface.php
namespace App\Domains\User\Repositories;

use App\Domains\User\Models\User;

interface UserRepositoryInterface
{
    public function findById(int $id): ?User;
    public function findByEmail(string $email): ?User;
    public function create(array $data): User;
    public function update(User $user, array $data): User;
    public function delete(User $user): bool;
    public function all(): \Illuminate\Database\Eloquent\Collection;
}
```

### 3. Repository Implementation
```php
<?php
// app/Domains/User/Repositories/UserRepository.php
namespace App\Domains\User\Repositories;

use App\Domains\User\Models\User;
use Illuminate\Support\Facades\Hash;

class UserRepository implements UserRepositoryInterface
{
    public function findById(int $id): ?User
    {
        return User::find($id);
    }

    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    public function create(array $data): User
    {
        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);
    }

    public function update(User $user, array $data): User
    {
        $user->update($data);
        return $user->fresh();
    }

    public function delete(User $user): bool
    {
        return $user->delete();
    }

    public function all(): \Illuminate\Database\Eloquent\Collection
    {
        return User::all();
    }
}
```

### 4. DTO классы
```php
<?php
// app/Domains/User/DTO/UserDto.php (для GET)
namespace App\Domains\User\DTO;

readonly class UserDto
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email
    ) {}
}

<?php
// app/Domains/User/DTO/CreateUserDto.php (для POST)
namespace App\Domains\User\DTO;

readonly class CreateUserDto
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password
    ) {}
}
```

### 5. Service класс
```php
<?php
// app/Domains/User/Services/UserService.php
namespace App\Domains\User\Services;

use App\Domains\User\Repositories\UserRepositoryInterface;
use App\Domains\User\DTO\UserDto;
use App\Domains\User\DTO\CreateUserDto;
use App\Domains\User\DTO\GetUserDto;
use App\Domains\User\Models\User;
use App\Domains\User\Exceptions\UserNotFoundException;

class UserService
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository
    ) {}

    public function getUserById(GetUserDto $dto): UserDto
    {
        $user = $this->userRepository->findById($dto->id);
        
        if (!$user) {
            throw new UserNotFoundException();
        }

        return new UserDto(
            id: $user->id,
            name: $user->name,
            email: $user->email
        );
    }

    public function createUser(CreateUserDto $dto): User
    {
        if ($this->userRepository->findByEmail($dto->email)) {
            throw new \Exception('User with this email already exists');
        }

        return $this->userRepository->create([
            'name' => $dto->name,
            'email' => $dto->email,
            'password' => $dto->password,
        ]);
    }

    public function updateUser(User $user, array $data): User
    {
        return $this->userRepository->update($user, $data);
    }

    public function deleteUser(User $user): bool
    {
        return $this->userRepository->delete($user);
    }
}
```

### 6. Resource класс
```php
<?php
// app/Domains/User/Http/Resources/UserResource.php
namespace App\Domains\User\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
```

### 7. Service Provider регистрация
```php
<?php
// app/Application/Providers/ApplicationServiceProvider.php
namespace App\Application\Providers;

use App\Domains\User\Repositories\UserRepositoryInterface;
use App\Domains\User\Repositories\UserRepository;
use Illuminate\Support\ServiceProvider;

class ApplicationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // User Domain bindings
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
    }
}
```

**Регистрация в config/app.php:**
```php
'providers' => [
    // ...
    App\Application\Providers\ApplicationServiceProvider::class,
],
```

---

## GET - Получение данных:

### Назначение: 
Безопасное получение данных, можно кешировать, закладывать в закладки.

### GET:

**Request класс:**
```php
<?php
// app/Domains/User/Http/Requests/GetUserRequest.php
namespace App\Domains\User\Http\Requests;

use App\Domains\User\DTO\GetUserDto;
use Illuminate\Foundation\Http\FormRequest;

class GetUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => ['required', 'integer', 'exists:users,id']
        ];
    }

    public function getDTO(): GetUserDto
    {
        return new GetUserDto(
            id: $this->validated('id')
        );
    }
}
```

**DTO класс:**
```php
<?php
// app/Domains/User/DTO/GetUserDto.php
namespace App\Domains\User\DTO;

declare(strict_types=1);

readonly class GetUserDto
{
    public function __construct(
        public int $id,
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
        ];
    }
}
```

**Controller:**
```php
<?php
// app/Domains/User/Http/Controllers/GetUserController.php
namespace App\Domains\User\Http\Controllers;

use App\Application\Http\Resources\NotFoundResource;
use App\Domains\User\Http\Requests\GetUserRequest;
use App\Domains\User\Http\Resources\UserResource;
use App\Domains\User\Services\UserService;
use App\Domains\User\Exceptions\UserNotFoundException;

class GetUserController
{
    public function __construct(
        private readonly UserService $userService
    ) {}

    public function __invoke(GetUserRequest $request): UserResource|NotFoundResource
    {
        try {
            $userDto = $this->userService->getUserById($request->getDTO());
            return new UserResource((object) [
                'id' => $userDto->id,
                'name' => $userDto->name,
                'email' => $userDto->email
            ]);
        } catch (UserNotFoundException) {
            return new NotFoundResource;
        }
    }
}
```

**Exception класс:**
```php
<?php
// app/Domains/User/Exceptions/UserNotFoundException.php
namespace App\Domains\User\Exceptions;

use Exception;

class UserNotFoundException extends Exception
{
}
```

**NotFoundResource:**
```php
<?php
// app/Application/Http/Resources/NotFoundResource.php
namespace App\Application\Http\Resources;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Symfony\Component\HttpFoundation\Response;

class NotFoundResource extends JsonResource
{
    public function __construct($resource = [])
    {
        parent::__construct($resource);
    }

    public function toArray(Request $request): array
    {
        return [
            'error' => 'Not Found',
        ];
    }

    public function withResponse(Request $request, JsonResponse $response): void
    {
        $response->setStatusCode(Response::HTTP_NOT_FOUND);
    }
}
```

**Роут:**
```php
Route::get('/users/{id}', GetUserController::class);
```

---

## POST - Создание данных:

### Назначение: 
Создание новых ресурсов. **CSRF токен обязателен** для защиты от повторной отправки формы.

### POST:

**Request класс:**
```php
<?php
// app/Domains/User/Http/Requests/StoreUserRequest.php
namespace App\Domains\User\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users'],
            'password' => ['required', 'min:8', 'confirmed'],
        ];
    }
}
```

**Controller:**
```php
<?php
// app/Domains/User/Http/Controllers/StoreUserController.php
namespace App\Domains\User\Http\Controllers;

use App\Domains\User\Http\Requests\StoreUserRequest;
use App\Domains\User\Http\Resources\UserResource;
use App\Domains\User\Services\UserService;
use App\Domains\User\DTO\CreateUserDto;

class StoreUserController
{
    public function __construct(
        private readonly UserService $userService
    ) {}

    public function __invoke(StoreUserRequest $request): UserResource
    {
        $dto = new CreateUserDto(
            name: $request->name,
            email: $request->email,
            password: $request->password
        );

        $user = $this->userService->createUser($dto);
        
        return new UserResource($user);
    }
}
```

**Роут:**
```php
Route::post('/users', StoreUserController::class);
```

**Форма с CSRF:**
```html
<form method="POST" action="/users">
    @csrf
    <input type="text" name="name" required>
    <input type="email" name="email" required>
    <input type="password" name="password" required>
    <input type="password" name="password_confirmation" required>
    <button type="submit">Create User</button>
</form>
```

---

## PUT - Полное обновление данных:

### Назначение: 
Полная замена ресурса. Все поля обязательны.

### PUT:

**Request класс:**
```php
<?php
// app/Domains/User/Http/Requests/UpdateUserRequest.php
namespace App\Domains\User\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email,' . $this->user->id],
        ];
    }
}
```

**Controller:**
```php
<?php
// app/Domains/User/Http/Controllers/UpdateUserController.php
namespace App\Domains\User\Http\Controllers;

use App\Domains\User\Http\Requests\UpdateUserRequest;
use App\Domains\User\Http\Resources\UserResource;
use App\Domains\User\Services\UserService;
use App\Domains\User\Models\User;

class UpdateUserController
{
    public function __construct(
        private readonly UserService $userService
    ) {}

    public function __invoke(UpdateUserRequest $request, User $user): UserResource
    {
        $updatedUser = $this->userService->updateUser($user, $request->validated());
        
        return new UserResource($updatedUser);
    }
}
```

**Роут:**
```php
Route::put('/users/{user}', UpdateUserController::class);
```

**Форма для PUT:**
```html
<form method="POST" action="/users/{{ $user->id }}">
    @csrf
    @method('PUT')
    <!-- все поля формы обязательны -->
</form>
```

---

## PATCH - Частичное обновление данных:

### Назначение: 
Частичное обновление. Только переданные поля изменяются.

### PATCH:

**Request класс:**
```php
<?php
// app/Domains/User/Http/Requests/PatchUserRequest.php
namespace App\Domains\User\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PatchUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'unique:users,email,' . $this->user->id],
        ];
    }
}
```

**Controller:**
```php
<?php
// app/Domains/User/Http/Controllers/PatchUserController.php
namespace App\Domains\User\Http\Controllers;

use App\Domains\User\Http\Requests\PatchUserRequest;
use App\Domains\User\Http\Resources\UserResource;
use App\Domains\User\Services\UserService;
use App\Domains\User\Models\User;

class PatchUserController
{
    public function __construct(
        private readonly UserService $userService
    ) {}

    public function __invoke(PatchUserRequest $request, User $user): UserResource
    {
        $updatedUser = $this->userService->updateUser($user, $request->validated());
        
        return new UserResource($updatedUser);
    }
}
```

**Роут:**
```php
Route::patch('/users/{user}', PatchUserController::class);
```

---

## DELETE - Удаление данных:

### Назначение: 
Удаление ресурса.

### DELETE:

**Resource:**
```php
<?php
// app/Domains/User/Http/Resources/DeleteUserResource.php
namespace App\Domains\User\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeleteUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'message' => 'User deleted successfully',
        ];
    }
}
```

**Controller:**
```php
<?php
// app/Domains/User/Http/Controllers/DeleteUserController.php
namespace App\Domains\User\Http\Controllers;

use App\Domains\User\Http\Resources\DeleteUserResource;
use App\Domains\User\Services\UserService;
use App\Domains\User\Models\User;

class DeleteUserController
{
    public function __construct(
        private readonly UserService $userService
    ) {}

    public function __invoke(User $user): DeleteUserResource
    {
        $this->userService->deleteUser($user);
        
        return new DeleteUserResource(null);
    }
}
```

**Роут:**
```php
Route::delete('/users/{user}', DeleteUserController::class);
```

**Форма для DELETE:**
```html
<form method="POST" action="/users/{{ $user->id }}">
    @csrf
    @method('DELETE')
    <button type="submit" onclick="return confirm('Are you sure?')">Delete</button>
</form>
```

---

## Детальная последовательность обработки запроса:

1. **HTTP Request** поступает на роут
2. **Middleware цепочка** (CSRF, Auth, Throttle и др.)
3. **Request класс** валидирует данные и создает DTO через `getDTO()`
4. **DTO** передает типизированные данные в Service
5. **Service** выполняет бизнес-логику, работает с Repository
6. **Repository** работает с Model (Eloquent)
7. **Service** возвращает результат (DTO или Model)
8. **Controller** создает Resource из результата
9. **Resource** форматирует ответ в JSON
10. **HTTP Response** отправляется клиенту
