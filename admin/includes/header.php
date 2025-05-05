<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>University Scheduler</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../style/style.css">
  <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.8/css/line.css">
  <!-- iconscout cdn -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<!-- google font Montserrat -->
<link href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
</head>
<body>
<nav>
    <div class="custom_container nav__container">
        <a href="index.html" class = "nav__logo">SmartSchedule</a>
        <ul class="nav__items">

            <li><a href="create_schedule.php">new schedule</a></li>
            <li><a href="view_calendar.php">view calendar</a></li>
            <li><a href="">edit calender</a></li>
            <li><a href="news.php">News</a></li>
            <li class="nav__profile">

                <div class="avatar">
                    <img src="../images/avatar1.jpg" alt="">
                </div>
                <ul>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="../logout.php">Logout</a></li>
                </ul>
            </li>
        </ul>
        <button id="open__nav-btn" > <i class="uil uil-bars"></i></button>
        <button id="close_-nav-btn"><i class="uil uil-multiply"></i></button>
    </div>

<div class="container">
