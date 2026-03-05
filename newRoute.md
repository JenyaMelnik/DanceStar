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
use App\Domains\User\Models\User;
use App\Domains\User\Exceptions\UserNotFoundException;
use Illuminate\Support\Facades\DB;

class UserService
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository
    ) {}

    public function getUserById(int $id): UserDto
    {
        $user = $this->userRepository->findById($id);
        
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
        return DB::transaction(function () use ($dto) {
            if ($this->userRepository->findByEmail($dto->email)) {
                throw new \Exception('User with this email already exists');
            }

            return $this->userRepository->create([
                'name' => $dto->name,
                'email' => $dto->email,
                'password' => $dto->password,
            ]);
        });
    }

    public function updateUser(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            return $this->userRepository->update($user, $data);
        });
    }

    public function deleteUser(User $user): bool
    {
        return DB::transaction(function () use ($user) {
            return $this->userRepository->delete($user);
        });
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

**Controller:**
```php
<?php
// app/Domains/User/Http/Controllers/GetUserController.php
namespace App\Domains\User\Http\Controllers;

use App\Application\Http\Resources\NotFoundResource;
use App\Domains\User\Http\Resources\UserResource;
use App\Domains\User\Services\UserService;
use App\Domains\User\Models\User;
use App\Domains\User\Exceptions\UserNotFoundException;

class GetUserController
{
    public function __construct(
        private readonly UserService $userService
    ) {}

    public function __invoke(User $user): UserResource|NotFoundResource
    {
        try {
            $userDto = $this->userService->getUserById($user->id);
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
    public function __construct()
    {
        parent::__construct(null);
    }
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
        
        return new DeleteUserResource;
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

---

# Авторизация в Laravel

### Что такое авторизация:
Авторизация - это процесс проверки прав доступа аутентифицированного пользователя к определенным ресурсам или действиям.

### Встроенные механизмы авторизации в Laravel:

#### 1. Gates (Шлюзы)
Gates - это простые замыкания, которые определяют, может ли пользователь выполнить определенное действие.

**Создание AuthServiceProvider:**
```php
<?php
// app/Providers/AuthServiceProvider.php
namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Domains\User\Models\User;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        // Определение Gates
        Gate::define('update-user', function (User $user, User $targetUser) {
            return $user->id === $targetUser->id;
        });

        Gate::define('delete-user', function (User $user) {
            return $user->email === 'admin@example.com';
        });

        Gate::define('view-admin-panel', function (User $user) {
            return $user->role === 'admin';
        });
    }
}
```

**Регистрация в config/app.php:**
```php
'providers' => [
    // ...
    App\Providers\AuthServiceProvider::class,
],
```

**Использование Gates в контроллерах:**
```php
<?php
// app/Domains/User/Http/Controllers/UpdateUserController.php
namespace App\Domains\User\Http\Controllers;

use App\Domains\User\Http\Requests\UpdateUserRequest;
use App\Domains\User\Http\Resources\UserResource;
use App\Domains\User\Services\UserService;
use App\Domains\User\Models\User;
use Illuminate\Support\Facades\Gate;
use App\Application\Http\Resources\UnauthorizedResource;

class UpdateUserController
{
    public function __construct(
        private readonly UserService $userService
    ) {}

    public function __invoke(UpdateUserRequest $request, User $user): UserResource|UnauthorizedResource
    {
        // Проверка через Gate
        if (!Gate::allows('update-user', $user)) {
            return new UnauthorizedResource;
        }

        $updatedUser = $this->userService->updateUser($user, $request->validated());
        
        return new UserResource($updatedUser);
    }
}
```

**Использование Gates в Request классах:**
```php
<?php
// app/Domains/User/Http/Requests/UpdateUserRequest.php
namespace App\Domains\User\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use App\Domains\User\Models\User;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Проверка авторизации через Gate
        return Gate::allows('update-user', $this->user);
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

#### 2. Policies (Политики)
Policies - это классы, которые организуют логику авторизации для конкретной модели.

**Создание Policy:**
```bash
php artisan make:policy UserPolicy --model=User
```

**Реализация Policy:**
```php
<?php
// app/Domains/User/Policies/UserPolicy.php
namespace App\Domains\User\Policies;

use App\Domains\User\Models\User;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return true; // Все пользователи могут просматривать список пользователей
    }

    public function view(User $user, User $model): bool
    {
        return $user->id === $model->id || $user->role === 'admin';
    }

    public function create(User $user): bool
    {
        return $user->role === 'admin';
    }

    public function update(User $user, User $model): bool
    {
        return $user->id === $model->id || $user->role === 'admin';
    }

    public function delete(User $user, User $model): bool
    {
        return $user->role === 'admin' && $user->id !== $model->id;
    }

    public function restore(User $user, User $model): bool
    {
        return $user->role === 'admin';
    }

    public function forceDelete(User $user, User $model): bool
    {
        return $user->role === 'super_admin';
    }
}
```

**Регистрация Policy в AuthServiceProvider:**
```php
<?php
// app/Providers/AuthServiceProvider.php
namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use App\Domains\User\Models\User;
use App\Domains\User\Policies\UserPolicy;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        User::class => UserPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
```

**Использование Policy в контроллере:**
```php
<?php
// app/Domains/User/Http/Controllers/DeleteUserController.php
namespace App\Domains\User\Http\Controllers;

use App\Domains\User\Http\Resources\DeleteUserResource;
use App\Domains\User\Services\UserService;
use App\Domains\User\Models\User;
use App\Application\Http\Resources\UnauthorizedResource;
use Illuminate\Auth\Access\AuthorizationException;

class DeleteUserController
{
    public function __construct(
        private readonly UserService $userService
    ) {}

    public function __invoke(User $user): DeleteUserResource|UnauthorizedResource
    {
        try {
            // Автоматическая проверка через Policy (метод delete)
            $this->authorize('delete', $user);
            
            $this->userService->deleteUser($user);
            
            return new DeleteUserResource;
        } catch (AuthorizationException) {
            return new UnauthorizedResource;
        }
    }
}
```

**Использование Policy в Request:**
```php
<?php
// app/Domains/User/Http/Requests/StoreUserRequest.php
namespace App\Domains\User\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Domains\User\Models\User;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Проверка через Policy (метод create)
        return $this->user()->can('create', User::class);
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

#### 3. Middleware авторизации
Laravel предоставляет встроенные middleware для проверки авторизации.

**Использование в роутах:**
```php
<?php
// routes/api.php
use Illuminate\Support\Facades\Route;
use App\Domains\User\Http\Controllers\GetUserController;
use App\Domains\User\Http\Controllers\DeleteUserController;

// Проверка на аутентификацию
Route::middleware('auth:api')->group(function () {
    // Проверка через Gate
    Route::middleware('can:view-admin-panel')->group(function () {
        Route::get('/admin/users', [AdminController::class, 'index']);
    });
    
    // Проверка через Policy
    Route::delete('/users/{user}', DeleteUserController::class)
         ->middleware('can:delete,user');
         
    // Пользователь может редактировать только свой профиль
    Route::put('/users/{user}', UpdateUserController::class)
         ->middleware('can:update,user');
});
```

#### 4. Проверка авторизации в Blade шаблонах
```blade
{{-- resources/views/dashboard.blade.php --}}

@can('update', $user)
    <button>Edit User</button>
@elsecan('view', $user)
    <p>View Only</p>
@else
    <p>No Access</p>
@endcan

@canany(['update', 'delete'], $user)
    <div>User Management Actions</div>
@endcanany

@cannot('delete', $user)
    <p>You cannot delete this user</p>
@endcannot
```

#### 5. Расширенная авторизация с ролями и правами

**Создание модели Role и Permission:**
```php
<?php
// app/Domains/Auth/Models/Role.php
namespace App\Domains\Auth\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    protected $fillable = ['name', 'description'];

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }
}

<?php
// app/Domains/Auth/Models/Permission.php
namespace App\Domains\Auth\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    protected $fillable = ['name', 'description'];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }
}
```

**Обновление модели User:**
```php
<?php
// app/Domains/User/Models/User.php
namespace App\Domains\User\Models;

use App\Domains\Auth\Models\Role;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class User extends Authenticatable implements JWTSubject
{
    // ... существующий код ...

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }

    public function hasRole(string $role): bool
    {
        return $this->roles()->where('name', $role)->exists();
    }

    public function hasPermission(string $permission): bool
    {
        return $this->permissions()->where('name', $permission)->exists() ||
               $this->roles()->whereHas('permissions', function ($query) use ($permission) {
                   $query->where('name', $permission);
               })->exists();
    }
}
```

**Gate с проверкой ролей и прав:**
```php
<?php
// app/Providers/AuthServiceProvider.php
public function boot(): void
{
    $this->registerPolicies();

    // Проверка прав через модель Permission
    Gate::before(function (User $user, string $ability) {
        if ($user->hasPermission($ability)) {
            return true;
        }
    });

    // Проверка ролей
    Gate::define('access-admin', function (User $user) {
        return $user->hasRole('admin');
    });
}
```

### Рекомендации по авторизации:

1. **Используйте Policies для моделей** - организует код лучше, чем Gates
2. **Проверяйте авторизацию в Request классах** - отделяет логику от контроллеров
3. **Используйте middleware в роутах** - для глобальных проверок доступа
4. **Реализуйте роли и права** - для сложных систем с разными уровнями доступа
5. **Логируйте попытки несанкционированного доступа** - для безопасности

### Пример полного цикла авторизации:

1. **Аутентификация** - пользователь вошел в систему (JWT токен)
2. **Проверка middleware** - `auth:api` проверяет валидность токена
3. **Проверка Policy** - `can:update,user` проверяет права на действие
4. **Выполнение действия** - если все проверки пройдены

---


