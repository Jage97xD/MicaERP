<?php
function builderComponentes($pdo){
    try{
        $stmt = $pdo->query("SELECT * FROM store_builder ORDER BY orden ASC, id ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }catch(Exception $e){
        return [];
    }
}
?>