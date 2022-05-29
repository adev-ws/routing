# Router 📌

Sistema de Rotas com Controllers e Middlewares

**como usar:**

inicialização:
```php
$router = new adevws\Router\Router("http://localhost/");

//rotas

//executar rota
$router->dispatch();
```
rotas
```php
$router->get('/', Controller::class, 'função_do_controller');
$router->get('/example', function (){
    return 'hello world';
});
```
definição de nome para rota
```php
$router->get(/**/)->name('example');
```

**Middlewares**

definição de middleware por rota
```php
$router->get(/**/)->middleware('auth');
```
```php
$router->get(/**/)->middleware(['auth', 'admin']);
```

definição de middlewares padrões
```php
// inicialização do rounter
$router->middlewares(['maintenance', 'auth']);
```

definição de middleware por controller
```php
use Tulipa\Collection\http\Controller;
class HomeController extends Controller {
    protected $middlewares = ['auth', 'admin'];
}
```

exemplo de middleware

```php
use Tulipa\Collection\http\Middleware\Middleware;
use Tulipa\Collection\http\Request;

class Auth extends Middleware {
    public function handle(Request $request){
        //codigo  
        return $request;
    }
}
```

**Controller**

exemplo de controller
```php
use Tulipa\Collection\http\Controller;
class HomeController extends Controller {

    //middlewares
    protected $middlewares = ['auth', 'admin'];
    
    public function index() {
        return 'Home Page'
    }
}
```