 <?php
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, POST, PUT");
    header("Access-Control-Allow-Headers: Content-Type");
    error_reporting(E_ALL);
ini_set("display_errors", 1);

$connect = new PDO('mysql:host=localhost;dbname=covoit', 'root','', array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'));

$received_data = json_decode(file_get_contents('php://input'));
// var_dump($received_data);
// echo "lol"
// $data = array();

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
    $query2 = "SET @row_id = 0; ";
    $query = "SELECT id_vehicles, @row_id := @row_id + 1 AS row_id, vehicles.id_motorization, libellet AS motorization, vehicles.id_user, vehicle_name, nb_places, color 
                  FROM `vehicles`, `motorization`, `users`
                  WHERE motorization.id_motorization = vehicles.id_motorization
                  AND vehicles.id_user = users.id_user
                  AND users.tel = $received_data->tel";
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
    $query2 = "SET @row_id = 0; ";
    $query = "SELECT id_vehicles, @row_id := @row_id + 1 AS row_id, vehicles.id_motorization, libellet AS motorization, vehicles.id_user, vehicle_name, nb_places, color 
                  FROM `vehicles`, `motorization`, `users`
                  WHERE motorization.id_motorization = vehicles.id_motorization
                  AND vehicles.id_user = users.id_user
                  AND users.tel = $received_data->tel";
    $statement2 = $connect->prepare($query2);
    $statement = $connect->prepare($query);
    $statement2->execute();
    $statement->execute();
    while($row = $statement->fetch(PDO::FETCH_ASSOC)) {
        $data[] = $row;
    }
    echo json_encode($data[$received_data->vehicleRowId - 1]);
}
if($received_data->action == 'delete_vehicle_for_user') {
    $query = "DELETE `vehicles` FROM `vehicles`,`users` 
                WHERE `vehicles`.`id_vehicles` = $received_data->vehicleId 
                AND vehicles.id_user = users.id_user 
                AND users.tel = '$received_data->tel'";
    $statement = $connect->prepare($query);
    $statement->execute();
    while($row = $statement->fetch(PDO::FETCH_ASSOC)) {
        $data[] = $row;
    }
    echo json_encode($data);
}
if($received_data->action == 'add_vehicle') {
    $query = "INSERT INTO `vehicles` (`id_vehicles`, `id_motorization`, `id_user`, `vehicle_name`, `nb_places`, `color` , `date_create`, `date_modification`) 
                VALUES (NULL, '$received_data->id_motorization', '$received_data->id_user', '$received_data->vehicle_name', '$received_data->nb_places', '$received_data->color', NOW(), NOW() );";
    $statement = $connect->prepare($query);
    $statement->execute();
    while($row = $statement->fetch(PDO::FETCH_ASSOC)) {
        $data[] = $row;
    }
    echo $query;
    echo json_encode($data);
}
if($received_data->action == 'fetchall_city') {
    $query = "SELECT ville_id, ville_nom_reel, ville_code_postal, ville_population_2012 FROM `villes_france_free` ORDER BY `ville_population_2012` DESC";
    $statement = $connect->prepare($query);
    $statement->execute();
    while($row = $statement->fetch(PDO::FETCH_ASSOC)) {
        $data[] = $row;
    }
    echo json_encode($data);
}

if($received_data->action == 'fetch_personnal_information') {
    $query = "SELECT * FROM `users`";
    $statement = $connect->prepare($query);
    $statement->execute();
    while($row = $statement->fetch(PDO::FETCH_ASSOC)) {
        $data[] = $row;
    }
    echo json_encode($data);
}

if($received_data->action == 'create_trip') {
    $starting_date = date("Y/m/d", strtotime($received_data->starting_date));
    $departure_time = $received_data->departure_time;
    $id_vehicles = $received_data->id_vehicles;
    $id_user = $received_data->id_user;
    $id_starting_point_city = $received_data->id_starting_point_city;
    $id_end_point_city = $received_data->id_end_point_city;
    $query = "
        BEGIN;
            INSERT INTO `trips` (`id_trip`, `date_create`, `starting_date`) 
                VALUES (NULL, NOW(), '$starting_date');
            SET @id_trip = LAST_INSERT_ID();
            INSERT INTO `paths` (`id_path`, `id_trip`, `departure_time`) 
                VALUES (NULL, @id_trip, '$departure_time');
            SET @id_path = LAST_INSERT_ID();
            INSERT INTO `driver` (`id_driver`, `id_trip`, `id_user`, `id_vehicles`) 
                VALUES (NULL, @id_trip, '$id_user', '$id_vehicles');
            INSERT INTO `starting_point` (`id_starting_point`, `id_path`, `id_city`) 
                VALUES (NULL, @id_path, '$id_starting_point_city');
            INSERT INTO `end_point` (`id_end_point`, `id_path`, `id_city`) 
                VALUES (NULL, @id_path, '$id_end_point_city');
        COMMIT;
    ";
    $statement = $connect->prepare($query);
    $statement->execute();
    while($row = $statement->fetch(PDO::FETCH_ASSOC)) {
        $data[] = $row;
    }
    echo $query;
    echo json_encode($data);
}

if($received_data->action == 'add_trip_passenger') {
    $trip_id = $received_data->trip_id;
    $departure_time = $received_data->departure_time;
    $id_user = $received_data->id_user;
    $id_starting_point_city = $received_data->id_starting_point_city;
    $query = "
        BEGIN;
            INSERT INTO `paths` (`id_path`, `id_trip`, `departure_time`) 
                VALUES (NULL, '$trip_id', '$departure_time')
            SET @id_path = LAST_INSERT_ID();
            INSERT INTO `starting_point` (`id_starting_point`, `id_path`, `id_city`) 
                VALUES (NULL, @id_path, '$id_starting_point_city')
            INSERT INTO `passenger` (`id_passenger`, `id_path`, `id_user`) 
                VALUES (NULL, @id_path, '$id_user')
        COMMIT;
    ";
    $statement = $connect->prepare($query);
    $statement->execute();
    while($row = $statement->fetch(PDO::FETCH_ASSOC)) {
        $data[] = $row;
    }
    echo $query;
    echo json_encode($data);
}
if($received_data->action == 'fetchall_trip_for_user') {
    $user_tel = $received_data->tel;
    // $query = "
    //     SELECT trips.id_trip, trips.date_create, trips.starting_date, paths.id_path, paths.departure_time, end_point.id_city AS end_point_city_id, starting_point.id_city AS starting_point_city_id, driver.id_driver, driver.id_vehicles, vehicles.vehicle_name, users.l_name, users.f_name
    //     FROM `trips`, `paths`, `end_point`, `starting_point`, `driver`, `vehicles`, `users`
    //     WHERE trips.id_trip = paths.id_trip
    //     AND driver.id_trip = trips.id_trip
    //     AND end_point.id_path = paths.id_path
    //     AND starting_point.id_path = paths.id_path
    //     AND vehicles.id_vehicles = driver.id_vehicles
    //     AND users.id_user = driver.id_user
    //     AND users.tel = '$user_tel'
    // ";
    $query = "
        SELECT *
        FROM trips
        JOIN paths
        ON trips.id_trip = paths.id_trip
        JOIN end_point
        ON paths.id_path = end_point.id_path
        JOIN driver
        ON trips.id_trip = driver.id_trip
        JOIN users
        On driver.id_user = users.id_user
        JOIN vehicles
        ON driver.id_vehicles = vehicles.id_vehicles
        JOIN villes_france_free
        ON end_point.id_city = villes_france_free.ville_id
    ";
    // $query = "
    //     SELECT *
    //     FROM trips
    // ";
    
    $statement = $connect->prepare($query);
    $statement->execute();
    while($row = $statement->fetch(PDO::FETCH_ASSOC)) {
        // print_r($row);

        $id_trip = $row['id_trip'];
        $query2 = "
            SELECT *
            FROM paths
            JOIN passenger
            ON paths.id_path = passenger.id_path
            JOIN users
            ON passenger.id_user = users.id_user
            WHERE paths.id_trip = $id_trip
        ";
        $statement2 = $connect->prepare($query2);
        $statement2->execute();
        while($row_path = $statement2->fetch(PDO::FETCH_ASSOC)) {
            $data_path[] = $row_path;
            $row['paths'] = $data_path;
        };
        $data[] = $row;
    }
    // echo $query;
    echo json_encode($data);
    // echo "<br>";
    // echo "<br>";
    // echo "<br>";
    // echo json_encode($data_path);
}

?>