<?php
include_once "header.php";

include_once "db_conn.php";

$id = $_SESSION['userid'];


// fetch updated user
$statement = $pdo->prepare("SELECT * FROM users WHERE id = :id");
$result = $statement->execute(array('id' => $id));
$user = $statement->fetch();


?>

<script src="js/profile.js"></script>


<div class="container bootstrap snippets bootdey">

    <div class="alert alert-success" id="alert-profile-update" style="display: none">
        <strong>Success!</strong> Profile was updated.
    </div>

    <h1 class="text-primary"><span class="glyphicon glyphicon-user"></span>Edit Profile</h1>
    <hr>
    <div class="row">
        <!-- left column -->
        <div class="col-md-1">
        </div>

        <!-- edit form column -->
        <div class="col-md-9 personal-info">
            <h3>Personal info</h3>
            <form class="form-horizontal" role="form" id="editForm" action="#" method="POST">
                <div class="form-group">
                    <label class="col-lg-3 control-label">First name:</label>
                    <div class="col-lg-8">
                        <input class="form-control" type="text" name="firstName" id="firstName"
                               value="<?php if (!empty($user['firstName'])) echo $user['firstName']; ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-lg-3 control-label">Last name:</label>
                    <div class="col-lg-8">
                        <input class="form-control" type="text" name="lastName" id="lastName"
                               value="<?php if (!empty($user['lastName'])) echo $user['lastName']; ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-lg-3 control-label">Company:</label>
                    <div class="col-lg-8">
                        <input class="form-control" type="text" name="company" id="company"
                               value="<?php if (!empty($user['company'])) echo $user['company']; ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label class="col-lg-3 control-label">Email:</label>
                    <div class="col-lg-8">
                        <input class="form-control" type="text" name="email" id="email"
                               value="<?php if (!empty($user['email'])) echo $user['email']; ?>">
                    </div>
                </div>

                <div class="container">
                    <div class="row">
                        <div class="col-md-9 bg-light text-right">

                            <button type="button" onclick="updateProfile()" class="btn btn-primary">
                                Update
                            </button>

                        </div>
                    </div>
                </div>

            </form>
        </div>
    </div>
</div>
<hr>

<?php
include_once "footer.php";
?>
