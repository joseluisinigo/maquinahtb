<?php

include "auth_check.php";

$error = "";

if (empty($_POST['firstName'])) {
    $error = 'First Name is required.';
} else if (empty($_POST['lastName'])) {
    $error = 'Last Name is required.';
} else if (empty($_POST['email'])) {
    $error = 'Email is required.';
} else if (empty($_POST['company'])) {
    $error = 'Company is required.';
}

if (!empty($error)) {
    die("Error updating profile, reason: " . $error);
} else {

    include "db_conn.php";

    $id = $_SESSION['userid'];
    $statement = $pdo->prepare("SELECT * FROM users WHERE id = :id");
    $result = $statement->execute(array('id' => $id));
    $user = $statement->fetch();

    if ($user !== false) {

        ini_set('display_errors', '1');
        ini_set('display_startup_errors', '1');
        error_reporting(E_ALL);

        $firstName = $_POST['firstName'];
        $lastName = $_POST['lastName'];
        $email = $_POST['email'];
        $company = $_POST['company'];
        $role = $user['role'];

        if (isset($_POST['role'])) {
            $role = $_POST['role'];
            $_SESSION['role'] = $role;
        }


        // dont persist role
        $sql = "UPDATE users SET firstName='$firstName', lastName='$lastName', email='$email', company='$company' WHERE id=$id";

        $stmt = $pdo->prepare($sql);
        $stmt->execute();

        $statement = $pdo->prepare("SELECT * FROM users WHERE id = :id");
        $result = $statement->execute(array('id' => $id));
        $user = $statement->fetch();

        // but return it to avoid confusion
        $user['role'] = $role;
        $user['6'] = $role;

        echo json_encode($user, JSON_PRETTY_PRINT);

    } else {
        echo "No user with this id was found.";
    }

}

?>
