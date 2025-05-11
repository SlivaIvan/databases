<?php
require('autoload.php');

// Базовый родительский класс с методами который умеет создавать БД и подключаться к таблице а так же создавать таблицу при необходимости

abstract class Database implements DatabaseWrapper {
    private $pdo;
    protected $tableName;

    public function __construct(string $dbPath)
    {

    try {
            $this->pdo = new PDO("sqlite:$dbPath");
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            echo "Подключение к базе данных успешно установлено!";
        } catch (PDOException $e) {
            die('Ошибка в подключении к базе данных'. $e->getMessage());
        }
        
    }


    public function createTable($reqHeader, $reqBody) {
        $sql = "CREATE TABLE $reqHeader($reqBody)";
        try {
            $this->pdo->exec($sql);
            echo "Таблица успешно создана!";
        } catch (PDOException $e) {
            die("Ошибка при создании таблицы: " . $e->getMessage());
        }
    }

// Вставляет новую запись в таблицу, возвращает полученный объект как массив
// Работает только для POST-запросов
public function insert(array $tableColumns, array $values): array {
    // Проверяем метод запроса - разрешаем только POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405); // Method Not Allowed
        throw new RuntimeException('Метод запроса должен быть POST');
    }

    // Проверяем, что количество колонок совпадает с количеством значений
    if (count($tableColumns) !== count($values)) {
        throw new InvalidArgumentException('Количество колонок не совпадает с количеством значений');
    }

    // Проверяем, что таблица установлена
    if (empty($this->tableName)) {
        throw new RuntimeException('Имя таблицы не установлено');
    }

    // Формируем SQL запрос
    $columns = implode(', ', $tableColumns);
    $placeholders = implode(', ', array_fill(0, count($values), '?'));
    $sql = "INSERT INTO {$this->tableName} ({$columns}) VALUES ({$placeholders})";
    
    // Начинаем транзакцию
    $this->pdo->beginTransaction();
    
    try {
        // Выполняем вставку
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($values);
        
        // Получаем ID последней вставленной записи
        $lastInsertId = $this->pdo->lastInsertId();
        
        // Получаем полную запись из базы
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->tableName} WHERE rowid = ?");
        $stmt->execute([$lastInsertId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Фиксируем транзакцию
        $this->pdo->commit();
        
        if (!$result) {
            throw new RuntimeException('Не удалось получить вставленную запись');
        }
        
        return $result;
    } catch (Exception $e) {
        // Откатываем транзакцию при ошибке
        $this->pdo->rollBack();
        throw $e;
    }
}

    // редактирует строку под конкретным id, возвращает результат после изменения
    public function update(int $id, array $values): array {
        if (empty($values)) {
            throw new InvalidArgumentException('Values array cannot be empty');
        }
    
        $tableName = $this->tableName;
        
        // Формируем SET часть запроса
        $setParts = [];
        $params = [];
        foreach ($values as $column => $value) {
            $setParts[] = "{$column} = ?";
            $params[] = $value;
        }
        $setClause = implode(', ', $setParts);
        $params[] = $id; // Добавляем ID в параметры для WHERE
    
        $sql = "UPDATE {$tableName} SET {$setClause} WHERE rowid = ?";
        
        // Начинаем транзакцию
        $this->pdo->beginTransaction();
        
        try {
            // Выполняем обновление
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            // Получаем обновленную запись
            $stmt = $this->pdo->prepare("SELECT * FROM {$tableName} WHERE rowid = ?");
            $stmt->execute([$id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $this->pdo->commit();
            
            return $result ?: [];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
    
    public function find(int $id): array {
        $sql = "SELECT * FROM {$this->tableName} WHERE rowid = ? LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    public function delete(int $id): bool {
    $sql = "DELETE FROM {$this->tableName} WHERE rowid = ?";
    $stmt = $this->pdo->prepare($sql);
    return $stmt->execute([$id]);
}

    public function disconnect() {
        $this->pdo = null;
    }

    /**
     * Деструктор класса (автоматически отключается при уничтожении объекта)
     */
    public function __destruct() {
        $this->disconnect();
    }
}