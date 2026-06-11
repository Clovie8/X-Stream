<?php 
session_start();
include 'connection.php';

// PHP Code to Delete Movie
if (isset($_GET['mid']) && !empty($_GET['mid'])) {
    $mid = $_GET['mid'];

    $delete_movie = "DELETE FROM `movies` WHERE id = $mid LIMIT 1";
    $execut_movie = mysqli_query($connect, $delete_movie);

    if ($execut_movie) {
        $_SESSION['end'] = "Movie Deleted Successfuly";
        header('location:../../movies');
        exit();
    }else{
        $_SESSION['start'] = "Deleting Movie Fail!";
        header('location:../../movies');
        exit();
    }
}

// PHP Code to Delete Serie
if (isset($_GET['sid']) && !empty($_GET['sid'])) {
    $sid = $_GET['sid'];

    $delete_serie = "DELETE FROM `series` WHERE id = $sid LIMIT 1";
    $execut_serie = mysqli_query($connect, $delete_serie);

    if ($execut_serie) {
        $_SESSION['end'] = "Serie Deleted Successfuly";
        header('location:../../series');
        exit();
    }else{
        $_SESSION['start'] = "Deleting Serie Fail!";
        header('location:../../series');
        exit();
    }
}

// PHP Code to Delete Episode
if (isset($_GET['eid']) && !empty($_GET['eid'])) {
    $eid = $_GET['eid'];

    $delete_episode = "DELETE FROM `episodes` WHERE id = $eid LIMIT 1";
    $execut_episode = mysqli_query($connect, $delete_episode);

    if ($execut_episode) {
        $_SESSION['end'] = "Episode Deleted Successfuly";
        header('location:../../episodes');
        exit();
    }else{
        $_SESSION['start'] = "Deleting Episode Fail!";
        header('location:../../episodes');
        exit();
    }
}

// PHP Code to Delete comment
if (isset($_GET['cid']) && !empty($_GET['cid'])) {
    $cid = $_GET['cid'];

    $delete_comment = "DELETE FROM `comments` WHERE id = $cid LIMIT 1";
    $execut_comment = mysqli_query($connect, $delete_comment);

    if ($execut_comment) {
        $_SESSION['end'] = "Comment Deleted Successfuly";
        header('location:../../comment');
        exit();
    }else{
        $_SESSION['start'] = "Deleting Comment Fail!";
        header('location:../../comment');
        exit();
    }
}
?>