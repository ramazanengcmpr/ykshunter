<?php

class Router {
    private string $method;
    private string $path;

    public function __construct() {
        $this->method = $_SERVER['REQUEST_METHOD'];
        $uri = $_SERVER['REQUEST_URI'];
        $parsed = parse_url($uri);
        $this->path = rtrim($parsed['path'] ?? '/', '/');
    }

    public function dispatch(): void {
        $segments = explode('/', ltrim($this->path, '/'));

        // Accept both /api/{resource}/... (when hosted behind an /api rewrite)
        // and /{resource}/... (when the PHP server is the host root, e.g. Render).
        if (($segments[0] ?? '') === 'api') {
            array_shift($segments);
        }

        $resource = $segments[0] ?? '';
        $action   = $segments[1] ?? '';
        $id       = $segments[2] ?? null;

        match($resource) {
            'quiz'    => $this->handleQuiz($action, $id),
            'auth'    => $this->handleAuth($action),
            'results' => $this->handleResult($action, $id),
            default   => $this->send404(),
        };
    }

    private function handleQuiz(string $action, ?string $id): void {
        $ctrl = new QuizController();
        match(true) {
            $action === 'categories' && $this->method === 'GET' => $ctrl->categories(),
            $action === 'list'       && $this->method === 'GET' => $ctrl->list(),
            $action === 'start'      && $this->method === 'POST' => $ctrl->start(),
            $action === 'question'   && $this->method === 'GET'  => $ctrl->question($id),
            $action === 'submit'     && $this->method === 'POST' => $ctrl->submit(),
            $action === 'finish'     && $this->method === 'POST' => $ctrl->finish(),
            default => $this->send404(),
        };
    }

    private function handleAuth(string $action): void {
        $ctrl = new AuthController();
        match(true) {
            $action === 'register' && $this->method === 'POST' => $ctrl->register(),
            $action === 'login'    && $this->method === 'POST' => $ctrl->login(),
            $action === 'me'       && $this->method === 'GET'  => $ctrl->me(),
            $action === 'users'    && $this->method === 'GET'  => $ctrl->users(),
            default => $this->send404(),
        };
    }

    private function handleResult(string $action, ?string $id): void {
        $ctrl = new ResultController();
        match(true) {
            $action === 'list'   && $this->method === 'GET' => $ctrl->list(),
            $action === 'detail' && $this->method === 'GET' => $ctrl->detail($id),
            default => $this->send404(),
        };
    }

    private function send404(): void {
        http_response_code(404);
        echo json_encode(['error' => 'Not found']);
    }
}
