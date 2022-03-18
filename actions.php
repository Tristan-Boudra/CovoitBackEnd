<?php
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, POST, PUT");
    header("Access-Control-Allow-Headers: Content-Type");

$connect = new PDO('mysql:host=localhost;dbname=covoit', 'root','', array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'));

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
    // echo $query2;
    echo json_encode($data[$received_data->vehicleRowId - 1]);
}

//Supprimer un véhicule
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
    // $id_user = htmlspecialchars($received_data->id_user);
    $userTel = htmlspecialchars($received_data->userTel);
    $id_motorization = htmlspecialchars($received_data->id_motorization);
    $vehicle_name = htmlspecialchars($received_data->vehicle_name);
    $nb_places = htmlspecialchars($received_data->nb_places);
    $color = htmlspecialchars($received_data->color);
    $query = "SELECT users.id_user FROM users WHERE users.tel = '$userTel'";
    $statement = $connect->prepare($query);
    $statement->execute();
    $idUser = $statement->fetch(PDO::FETCH_ASSOC);
    $idUser = $idUser['id_user'];
    // echo $idUser;
    $query2 = "
        INSERT INTO `vehicles` (`id_vehicles`, `id_motorization`, `id_user`, `vehicle_name`, `nb_places`, `color`, `date_create`, `date_modification`) 
        VALUES (NULL, '$id_motorization', '$idUser', '$vehicle_name', '$nb_places', '$color', NOW(), NOW());
    ";
    $statement2 = $connect->prepare($query2);
    $statement2->execute();
    // while($row = $statement2->fetch(PDO::FETCH_ASSOC)) {
    //     $data[] = $row;
    // }
    // echo $query2;
    // echo json_encode($data);
}

//Informations personnelles
if($received_data->action == 'fetch_personal_information') {
    $userTel = htmlspecialchars($received_data->userTel);
    // $userName = htmlspecialchars($received_data->userName);
    // $userSurname = htmlspecialchars($received_data->userSurname);
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

    if($user_password_hashed == $user_password_confirmed_hashed){
        $query = "INSERT INTO `users` (`l_name`, `f_name`, `tel`, `password`, `date_create`,`date_modification`) 
                    VALUES ('$surname', '$name', '$tel', '$user_password_hashed', NOW(), NOW());";
        $statement = $connect->prepare($query);
        $statement->execute();
        echo json_encode("OK");
    } else { echo("Mauvais mot de passe"); }
    while($row = $statement->fetch(PDO::FETCH_ASSOC)) {
        $data[] = $row;
    }
    // echo $query;
    //echo json_encode($data);
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
        // echo $user_password_hashed;
        if ($user_password_hashed === $resultPassword) {
            echo json_encode("OK");
        }  else { echo("Mauvais password");}
    } else { echo("Mauvais tel ou password"); }
    // echo $query;
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

// Recupere toutes les ville
if($received_data->action == 'fetchall_city') {
    $query = "SELECT ville_id, ville_nom_reel, ville_code_postal, ville_population_2012 FROM `villes_france_free` ORDER BY `ville_population_2012` DESC";
    $statement = $connect->prepare($query);
    $statement->execute();
    while($row = $statement->fetch(PDO::FETCH_ASSOC)) {
        $data[] = $row;
    }
    echo json_encode($data);
}

// Créer un voyage
if($received_data->action == 'create_trip') {
    $starting_date = date("Y/m/d", strtotime($received_data->starting_date));
    $departure_time = $received_data->departure_time;
    $id_vehicles = $received_data->id_vehicles;
    $userTel = $received_data->userTel;
    $id_starting_point_city = $received_data->id_starting_point_city;
    $id_end_point_city = $received_data->id_end_point_city;
    $query = "SELECT users.id_user FROM users WHERE users.tel = '$userTel'";
    $statement = $connect->prepare($query);
    $statement->execute();
    $idUser = $statement->fetch(PDO::FETCH_ASSOC);
    $idUser = $idUser['id_user'];
    $query = "
        BEGIN;
            INSERT INTO `trips` (`id_trip`, `date_create`, `starting_date`) 
                VALUES (NULL, NOW(), '$starting_date');
            SET @id_trip = LAST_INSERT_ID();
            INSERT INTO `paths` (`id_path`, `id_trip`, `departure_time`) 
                VALUES (NULL, @id_trip, '$departure_time');
            SET @id_path = LAST_INSERT_ID();
            INSERT INTO `driver` (`id_driver`, `id_trip`, `id_user`, `id_vehicles`) 
                VALUES (NULL, @id_trip, '$idUser', '$id_vehicles');
            INSERT INTO `starting_point` (`id_starting_point`, `id_path`, `id_city`) 
                VALUES (NULL, @id_path, '$id_starting_point_city');
            INSERT INTO `end_point` (`id_end_point`, `id_path`, `id_city`) 
                VALUES (NULL, @id_path, '$id_end_point_city');
        COMMIT;
    ";
    $statement = $connect->prepare($query);
    $statement->execute();
    // while($row = $statement->fetch(PDO::FETCH_ASSOC)) {
    //     $data[] = $row;
    // }
    // echo $query;
    // echo json_encode($data);
}

// Ajoute un passager a un voyage
// ATTENTION EREUR POSSIBLE
if($received_data->action == 'add_trip_passenger') {
    $idTrip = $received_data->idTrip;
    $departureTime = $received_data->departureTime;
    $userTel = $received_data->UserTel;
    $startingPointCity = $received_data->startingPointCity;
    
    $query = "SELECT id_user FROM users WHERE tel = '$userTel'";
    $statement = $connect->prepare($query);
    $statement->execute();
    $idUser = $statement->fetch(PDO::FETCH_ASSOC);
    $idUser = $idUser['id_user'];

    $query2 = "SELECT ville_id FROM `villes_france_free` WHERE ville_nom_simple = '$startingPointCity'";
    $statement2 = $connect->prepare($query2);
    $statement2->execute();
    $idCity = $statement2->fetch(PDO::FETCH_ASSOC);
    $idCity = $idCity['ville_id'];

    $query3 = "
        BEGIN;
            SELECT users.id_user FROM users WHERE users.tel = '$userTel';
            SELECT ville_id FROM `villes_france_free` WHERE ville_nom_simple = '$startingPointCity';
            INSERT INTO `paths` (`id_path`, `id_trip`, `departure_time`) 
                VALUES (NULL, '$idTrip', '$departureTime');
            SET @id_path = LAST_INSERT_ID();
            INSERT INTO `starting_point` (`id_starting_point`, `id_path`, `id_city`) 
                VALUES (NULL, @id_path, $idCity);
            INSERT INTO `passenger` (`id_passenger`, `id_path`, `id_user`) 
                VALUES (NULL, @id_path, $idUser);
        COMMIT;
    ";

    // ATTention error
    $statement3 = $connect->prepare($query3);
    $statement3->execute();
    // while($row = $statement3->fetch(PDO::FETCH_ASSOC)) {
    //     $data[] = $row;
    // }
    echo $query;
    echo $query2;
    echo $query3;
    // echo json_encode($data);
}

if($received_data->action == 'fetchall_trip_for_user') {
    $data = [];
    $user_tel = $received_data->tel;
    $query = "
        SELECT starting_date, departure_time, ville_nom_reel AS end_point_city, end_point.id_city AS starting_point_id_city, l_name, f_name, vehicle_name, color, paths.id_trip, paths.id_path
        FROM trips
        JOIN paths
        ON trips.id_trip = paths.id_trip
        JOIN end_point
        ON paths.id_path = end_point.id_path
        JOIN starting_point
        ON paths.id_path = starting_point.id_path
        JOIN driver
        ON trips.id_trip = driver.id_trip
        JOIN users
        On driver.id_user = users.id_user
        JOIN vehicles
        ON driver.id_vehicles = vehicles.id_vehicles
        JOIN villes_france_free
        ON end_point.id_city = villes_france_free.ville_id
        WHERE users.tel = '$user_tel'
        ORDER BY trips.starting_date
    ";
    $statement = $connect->prepare($query);
    $statement->execute();
    while($row = $statement->fetch(PDO::FETCH_ASSOC)) {
        $id_path = $row['id_path'];
        $query2 = "
            SELECT `villes_france_free`.`ville_nom_reel` AS starting_point_city
            FROM `starting_point`
            JOIN villes_france_free
            ON starting_point.id_city = villes_france_free.ville_id
            WHERE id_path = $id_path
            ";
            $statement2 = $connect->prepare($query2);
            $statement2->execute();
            $row_city_name = $statement2->fetch(PDO::FETCH_ASSOC);
            $row += $row_city_name;
            
            $id_trip = $row['id_trip'];
            $query3 = "
            SELECT *
            FROM paths, users, passenger, starting_point, villes_france_free
            WHERE paths.id_path = passenger.id_path
            AND passenger.id_user = users.id_user
            AND starting_point.id_path = paths.id_path
            AND starting_point.id_city = villes_france_free.ville_id
            AND paths.id_trip = $id_trip
            ";
            $statement3 = $connect->prepare($query3);
            $statement3->execute();
            while($row_path = $statement3->fetch(PDO::FETCH_ASSOC)) {
                $data_path[] = $row_path;
                $row['paths'] = $data_path;
            };
        $data[] = $row;
    }
    echo json_encode($data);
}
if($received_data->action == 'fetchall_trip_for_user_up_to_date') {
    $data = [];
    $user_tel = $received_data->tel;
    $query = "
        SELECT starting_date, departure_time, ville_nom_reel AS end_point_city, end_point.id_city AS starting_point_id_city, l_name, f_name, vehicle_name, color, paths.id_trip, paths.id_path
        FROM trips
        JOIN paths
        ON trips.id_trip = paths.id_trip
        JOIN end_point
        ON paths.id_path = end_point.id_path
        JOIN starting_point
        ON paths.id_path = starting_point.id_path
        JOIN driver
        ON trips.id_trip = driver.id_trip
        JOIN users
        On driver.id_user = users.id_user
        JOIN vehicles
        ON driver.id_vehicles = vehicles.id_vehicles
        JOIN villes_france_free
        ON end_point.id_city = villes_france_free.ville_id
        WHERE users.tel = '$user_tel'
        AND trips.starting_date >= NOW()
        ORDER BY trips.starting_date
        ";
        $statement = $connect->prepare($query);
        $statement->execute();
        while($row = $statement->fetch(PDO::FETCH_ASSOC)) {
            $id_path = $row['id_path'];
            $query2 = "
            SELECT `villes_france_free`.`ville_nom_reel` AS starting_point_city
            FROM `starting_point`
            JOIN villes_france_free
            ON starting_point.id_city = villes_france_free.ville_id
            WHERE id_path = $id_path
            ";
            $statement2 = $connect->prepare($query2);
            $statement2->execute();
            $row_city_name = $statement2->fetch(PDO::FETCH_ASSOC);
            $row += $row_city_name;
            
            $id_trip = $row['id_trip'];
            $query3 = "
            SELECT *
            FROM paths, users, passenger, starting_point, villes_france_free
            WHERE paths.id_path = passenger.id_path
            AND passenger.id_user = users.id_user
            AND starting_point.id_path = paths.id_path
            AND starting_point.id_city = villes_france_free.ville_id
            AND paths.id_trip = $id_trip
            ";
            $statement3 = $connect->prepare($query3);
            $statement3->execute();
            while($row_path = $statement3->fetch(PDO::FETCH_ASSOC)) {
                $data_path[] = $row_path;
                $row['paths'] = $data_path;
            };
        $data[] = $row;
    }
    echo json_encode($data);
}
if($received_data->action == 'fetchall_trip_for_endcity_up_to_date') {
    $data = [];
    $endpointCity = $received_data->endpointCity;
    $dateOfTravel = $received_data->dateOfTravel;
    $query = "
        SELECT starting_date, departure_time, ville_nom_reel AS end_point_city, end_point.id_city AS starting_point_id_city, l_name, f_name, vehicle_name, color, paths.id_trip, paths.id_path
        FROM trips
        JOIN paths
        ON trips.id_trip = paths.id_trip
        JOIN end_point
        ON paths.id_path = end_point.id_path
        JOIN starting_point
        ON paths.id_path = starting_point.id_path
        JOIN driver
        ON trips.id_trip = driver.id_trip
        JOIN users
        On driver.id_user = users.id_user
        JOIN vehicles
        ON driver.id_vehicles = vehicles.id_vehicles
        JOIN villes_france_free
        ON end_point.id_city = villes_france_free.ville_id
        WHERE villes_france_free.ville_nom_simple LIKE '$endpointCity%'
        AND trips.starting_date = '$dateOfTravel'
        ORDER BY trips.starting_date
    ";
    $statement = $connect->prepare($query);
    $statement->execute();
    while($row = $statement->fetch(PDO::FETCH_ASSOC)) {
        $id_path = $row['id_path'];
        $query2 = "
        SELECT `villes_france_free`.`ville_nom_reel` AS starting_point_city
        FROM `starting_point`
        JOIN villes_france_free
        ON starting_point.id_city = villes_france_free.ville_id
        WHERE id_path = $id_path
        ";
        $statement2 = $connect->prepare($query2);
        $statement2->execute();
        $row_city_name = $statement2->fetch(PDO::FETCH_ASSOC);
        $row += $row_city_name;
        
        $id_trip = $row['id_trip'];
        $query3 = "
        SELECT *
        FROM paths, users, passenger, starting_point, villes_france_free
        WHERE paths.id_path = passenger.id_path
        AND passenger.id_user = users.id_user
        AND starting_point.id_path = paths.id_path
        AND starting_point.id_city = villes_france_free.ville_id
        AND paths.id_trip = $id_trip
        ";
        $statement3 = $connect->prepare($query3);
        $statement3->execute();
        while($row_path = $statement3->fetch(PDO::FETCH_ASSOC)) {
            $data_path[] = $row_path;
            $row['paths'] = $data_path;
        };
    $data[] = $row;
    }
    // echo $query;
    echo json_encode($data);
}
if($received_data->action == 'fetchall_trip_for_endcity_aproximative_up_to_date') {
    $data = [];
    $endpointCity = $received_data->endpointCity;
    $dateOfTravel = $received_data->dateOfTravel;

    $parts = explode('-', $dateOfTravel);
    $MaxDate = date(
        'Y-m-d', 
        mktime(0, 0, 0, $parts[1], $parts[2] + 5, $parts[0])
        //              ^ Month    ^ Day + 5      ^ Year
    );
    $MinDate = date(
        'Y-m-d', 
        mktime(0, 0, 0, $parts[1], $parts[2] - 5, $parts[0])
        //              ^ Month    ^ Day + 5      ^ Year
    );
    $DatePlus1 = date(
        'Y-m-d', 
        mktime(0, 0, 0, $parts[1], $parts[2] + 1, $parts[0])
        //              ^ Month    ^ Day + 5      ^ Year
    );
    $DateMoin1 = date(
        'Y-m-d', 
        mktime(0, 0, 0, $parts[1], $parts[2] - 1, $parts[0])
        //              ^ Month    ^ Day + 5      ^ Year
    );
    $aproximativeDateMin = $received_data->dateOfTravel;
    $query = "
        SELECT starting_date, departure_time, ville_nom_reel AS end_point_city, end_point.id_city AS starting_point_id_city, l_name, f_name, vehicle_name, color, paths.id_trip, paths.id_path
        FROM trips
        JOIN paths
        ON trips.id_trip = paths.id_trip
        JOIN end_point
        ON paths.id_path = end_point.id_path
        JOIN starting_point
        ON paths.id_path = starting_point.id_path
        JOIN driver
        ON trips.id_trip = driver.id_trip
        JOIN users
        On driver.id_user = users.id_user
        JOIN vehicles
        ON driver.id_vehicles = vehicles.id_vehicles
        JOIN villes_france_free
        ON end_point.id_city = villes_france_free.ville_id
        WHERE villes_france_free.ville_nom_simple LIKE '$endpointCity%'
        AND trips.starting_date BETWEEN '$DatePlus1' AND '$MaxDate'
        OR trips.starting_date BETWEEN '$DateMoin1' AND '$MinDate'
        ORDER BY trips.starting_date
    ";
    $statement = $connect->prepare($query);
    $statement->execute();
    while($row = $statement->fetch(PDO::FETCH_ASSOC)) {
        $id_path = $row['id_path'];
        $query2 = "
        SELECT `villes_france_free`.`ville_nom_reel` AS starting_point_city
        FROM `starting_point`
        JOIN villes_france_free
        ON starting_point.id_city = villes_france_free.ville_id
        WHERE id_path = $id_path
        ";
        $statement2 = $connect->prepare($query2);
        $statement2->execute();
        $row_city_name = $statement2->fetch(PDO::FETCH_ASSOC);
        $row += $row_city_name;
        
        $id_trip = $row['id_trip'];
        $query3 = "
        SELECT *
        FROM paths, users, passenger, starting_point, villes_france_free
        WHERE paths.id_path = passenger.id_path
        AND passenger.id_user = users.id_user
        AND starting_point.id_path = paths.id_path
        AND starting_point.id_city = villes_france_free.ville_id
        AND paths.id_trip = $id_trip
        ";
        $statement3 = $connect->prepare($query3);
        $statement3->execute();
        while($row_path = $statement3->fetch(PDO::FETCH_ASSOC)) {
            $data_path[] = $row_path;
            $row['paths'] = $data_path;
        };
    $data[] = $row;
    }
    // echo $query;
    echo json_encode($data);
}
?> 