<?php
require __DIR__ . '/connect.php';
session_start();

if (!isset($_SESSION['tasks'])) {
    $_SESSION['tasks'] = array();
}

// Fetch tasks
$stmt_fetch = $conn->prepare("SELECT * FROM tasks");
$stmt_fetch->execute();
$stmt_fetch->setFetchMode(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle file upload
    $file_name = '';
    if (isset($_FILES['task_image']) && $_FILES['task_image']['error'] == 0) {
        $ext = strtolower(pathinfo($_FILES['task_image']['name'], PATHINFO_EXTENSION));
        $file_name = md5(date('Y.m.d.H.i.s')) . '.' . $ext;
        $dir = 'uploads/';

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        if (!move_uploaded_file($_FILES['task_image']['tmp_name'], $dir . $file_name)) {
            $_SESSION['message'] = "Falha ao mover o arquivo para a pasta de uploads.";
        }
    }

    if (isset($_POST['task_name']) && !empty($_POST['task_name'])) {
        $stmt = $conn->prepare('INSERT INTO tasks (task_name, task_description, task_image, task_date) 
                                VALUES (:name, :description, :image, :date)');
        $stmt->bindParam(':name', $_POST['task_name'], PDO::PARAM_STR);
        $stmt->bindParam(':description', $_POST['task_description'], PDO::PARAM_STR);
        if (!empty($file_name)) {
            $stmt->bindParam(':image', $file_name, PDO::PARAM_STR);
        } else {
            $stmt->bindValue(':image', null, PDO::PARAM_NULL);
        }
        $stmt->bindParam(':date', $_POST['task_date'], PDO::PARAM_STR);

        try {
            if ($stmt->execute()) {
                $_SESSION['success'] = "Dados cadastrados";
            } else {
                $_SESSION['error'] = "Dados não cadastrados";
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = "Erro ao executar a consulta: " . $e->getMessage();
        }
    } else {
        $_SESSION['message'] = "O campo nome da tarefa não pode ser vazio!";
    }

    if (isset($_POST['key'])) {
        array_splice($_SESSION['tasks'], $_POST['key'], 1);
    }

    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

if (isset($_GET['clear'])) {
    unset($_SESSION['tasks']);
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Ubuntu:ital,wght@0,300;0,400;0,500;0,700;1,300;1,400;1,500;1,700&display=swap" rel="stylesheet">
    <title>Gerenciador de Tarefas</title>
</head>
<body>
    <div class="container">
        <?php 
            if(isset($_SESSION['success'])) {
        ?>
            <div class="alert-success"><?php echo $_SESSION['success']; ?></div>
        <?php
            unset($_SESSION['success']);
            }
        ?>

        <?php 
            if(isset($_SESSION['error'])) {
        ?>
            <div class="alert-error"><?php echo $_SESSION['error']; ?></div>
        <?php
            unset($_SESSION['error']);
            }
        ?>
        <div class="header">
            <h1>Gerenciador de Tarefas</h1>
        </div>

        <div class="form">
            <form action="" method="POST" enctype="multipart/form-data">
                <label for="task_name">Tarefa: </label>
                <input type="text" name="task_name" placeholder="Nome da Tarefa" required>
                <label for="task_description">Descrição: </label>
                <input type="text" name="task_description" placeholder="Descrição da Tarefa">
                <label for="task_date">Data: </label>
                <input type="date" name="task_date">
                <label for="task_image">Imagem: </label>
                <input type="file" name="task_image">
                <button type="submit">Cadastrar</button>
            </form>
        </div>

        <div class="separator"></div>
        
        <div class="list-tasks">
            <?php
            if (!empty($stmt_fetch)) {
                echo "<ul>";
                foreach ($stmt_fetch->fetchAll() as $task) {
                    echo "<li>
                        <a href='details.php?key=" . $task['id'] . "'>" . $task['task_name'] . "</a>             
                    </li>";
                }
                echo "</ul>";
            }
            ?>
        </div>        
        <form action="" method="GET">
            <input type="hidden" name="clear" value="clear">
            <button type="submit" class="btn-clear">Limpar Tarefas</button>
        </form>
        
        <?php
        if (isset($_SESSION['message'])) {
            echo "<p style='color: #EF5350;'>" . htmlspecialchars($_SESSION['message']) . "</p>";
            unset($_SESSION['message']);
        }
        ?>
    <div class="footer">
        <p>Desenvolvido por @alvesmariadefatima</p>
    </div>
</div>
</body>
</html>