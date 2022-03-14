<?php
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, POST, PUT");
    header("Access-Control-Allow-Headers: Content-Type");

$connect = new PDO('mysql:host=localhost;dbname=covoit', 'root','');

$received_data = json_decode(file_get_contents('php://input'));

//Toute les motorisation
if($received_data->action == 'fetchall_motorization') {
    $query = 'SELECT id_motorization,libellet FROM `motorization`';
    $statement = $connect->prepare($query);
    $statement->execute();
    while($row = $statement->fetch(PDO::FETCH_ASSOC)) {
        $data[] = $row;
    }
    echo json_encode($data);
}
if($received_data->action == 'fetchall_vehicles_for_user') {
    $tel = htmlspecialchars($received_data->tel);
    $query2 = "SET @row_id = 0; ";
    $query = "SELECT id_vehicles, @row_id := @row_id + 1 AS row_id, vehicles.id_motorization, libellet AS motorization, vehicles.id_user, vehicle_name, nb_places, color 
                  FROM `vehicles`, `motorization`, `users`
                  WHERE motorization.id_motorization = vehicles.id_motorization
                  AND vehicles.id_user = users.id_user
                  AND users.tel = $tel";
    $statement2 = $connect->prepare($query2);
    $statement = $connect->prepare($query);
    $statement2->execute();
    $statement->execute();
    while($row = $statement->fetch(PDO::FETCH_ASSOC)) {
        $data[] = $row;
    }
    if (!empty($data)) {
        echo json_encode($data);
    }
}
if($received_data->action == 'fetch_vehicle_for_user') {
    $tel = htmlspecialchars($received_data->tel);
    $query2 = "SET @row_id = 0; ";
    $query = "SELECT id_vehicles, @row_id := @row_id + 1 AS row_id, vehicles.id_motorization, libellet AS motorization, vehicles.id_user, vehicle_name, nb_places, color 
                  FROM `vehicles`, `motorization`, `users`
                  WHERE motorization.id_motorization = vehicles.id_motorization
                  AND vehicles.id_user = users.id_user
                  AND users.tel = $tel";
    $statement2 = $connect->prepare($query2);
    $statement = $connect->prepare($query);
    $statement2->execute();
    $statement->execute();
    while($row = $statement->fetch(PDO::FETCH_ASSOC)) {
        $data[] = $row;
    }
    echo json_encode($data[$received_data->vehicleRowId - 1]);
}

//Supprimer une véhicule
if($received_data->action == 'delete_vehicle_for_user') {
    $vehicleId = htmlspecialchars($received_data->vehicleId);
    $tel = htmlspecialchars($received_data->tel);
    $query = "DELETE `vehicles` FROM `vehicles`,`users` 
                WHERE `vehicles`.`id_vehicles` = $vehicleId 
                AND vehicles.id_user = users.id_user 
                AND users.tel = '$tel'";
    $statement = $connect->prepare($query);
    $statement->execute();
    while($row = $statement->fetch(PDO::FETCH_ASSOC)) {
        $data[] = $row;
    }
    echo json_encode($data);
}

//Ajouter un véhicule
if($received_data->action == 'add_vehicle') {
    $id_motorization = htmlspecialchars($received_data->id_motorization);
    $id_user = htmlspecialchars($received_data->id_user);
    $vehicle_name = htmlspecialchars($received_data->vehicle_name);
    $nb_places = htmlspecialchars($received_data->nb_places);
    $color = htmlspecialchars($received_data->color);
    $query = "INSERT INTO `vehicles` (`id_vehicles`, `id_motorization`, `id_user`, `vehicle_name`, `nb_places`, `color`) 
                VALUES (NULL, '$id_motorization', '$id_user', '$vehicle_name', '$nb_places', '$color');";
    $statement = $connect->prepare($query);
    $statement->execute();
    while($row = $statement->fetch(PDO::FETCH_ASSOC)) {
        $data[] = $row;
    }
    echo json_encode($data);
}

//Informations personnelles
if($received_data->action == 'fetch_personal_information') {
    $userTel = htmlspecialchars($received_data->userTel);
    //$userName = htmlspecialchars($received_data->userName);
    //$userSurname = htmlspecialchars($received_data->userSurname);
    $query = "SELECT * FROM `users` WHERE tel = '$userTel'";
    $statement = $connect->prepare($query);
    $statement->execute();
    while($row = $statement->fetch(PDO::FETCH_ASSOC)) {
        $data[] = $row;
    }
    echo json_encode($data[0]);
}

//Inscription
if($received_data->action == 'new_registration') {
    $surname = htmlspecialchars($received_data->surname);
    $name = htmlspecialchars($received_data->name);
    $tel = htmlspecialchars($received_data->tel);
    $password = htmlspecialchars($received_data->password);
    $password_confirmed = htmlspecialchars($received_data->password_confirmed);
    $user_password_hashed = hash('sha256', $password);
    $user_password_confirmed_hashed = hash('sha256', $password_confirmed);
    
    $query2 = "SELECT tel from `users` WHERE tel= '$tel'";
    $statement2 = $connect->prepare($query2);
    $statement2->execute();
    $row = $statement2->fetch(PDO::FETCH_ASSOC);
    
    $resultTel = $row['tel'];
    if ($resultTel != $tel) {
        if($user_password_hashed == $user_password_confirmed_hashed){
            $query = "INSERT INTO `users` (`l_name`, `f_name`, `tel`, `password`, `date_create`, `date_modification`) 
                        VALUES ('$surname', '$name', '$tel', '$user_password_hashed', NOW(), NOW());";
            $statement = $connect->prepare($query);
            $statement->execute();
            echo json_encode("OK");
        } else { echo("Mauvais mot de passe"); }
        while($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $data[] = $row;
        }
        // echo json_encode($data);
    } else { echo("Vous avez déjà un compte"); }
}


//Login
if($received_data->action == 'verif_login') {
    $user_tel = htmlspecialchars($received_data->tel);
    $user_password = htmlspecialchars($received_data->password);
    $query = "SELECT tel,password FROM `users` WHERE tel= '$user_tel'";
    $statement = $connect->prepare($query);
    $statement->execute();
    $row = $statement->fetchAll(PDO::FETCH_ASSOC);
    
    $resultTel = $row[0]['tel'];
    $resultPassword = $row[0]['password'];
    if($resultTel == $user_tel){
        $user_password_hashed = hash('sha256', $user_password);
        if ($user_password_hashed === $resultPassword) {
            echo json_encode("OK");
        }  else { echo("Mauvais password");}
    } else { echo("Mauvais tel ou password"); }
}

//Edit password
if($received_data->action == 'fetch_edit_password') {
    $user_old_password = htmlspecialchars($received_data->old_password);
    $user_new_password = htmlspecialchars($received_data->new_password);
    $user_new_password_confirmed = htmlspecialchars($received_data->new_password_confirmed);
    $user_tel = htmlspecialchars($received_data->tel);
    $old_password_hashed = hash('sha256', $user_old_password);
    $new_password_hashed = hash('sha256', $user_new_password);
    $new_password_confirmed_hashed = hash('sha256', $user_new_password_confirmed);

    $query2 = "SELECT password FROM `users` WHERE password= '$old_password_hashed'";
    $statement2 = $connect->prepare($query2);
    $statement2->execute();
    $row = $statement2->fetch(PDO::FETCH_ASSOC);

    $resultPassword = $row['password'];
    if($resultPassword == $old_password_hashed){
      echo json_encode("old_password_ok");
        if($new_password_hashed == $new_password_confirmed_hashed){
            $query = "UPDATE `users` SET `password` = '$new_password_hashed' WHERE tel= '$user_tel';";
            $statement = $connect->prepare($query);
            $statement->execute();
            echo json_encode("password_confirmed_ok");
        } else { echo json_encode("password_confirmed_incorrect"); }
    } else { echo json_encode("old_password_incorrect"); }
}
?> 