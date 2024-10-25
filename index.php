<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

require_once 'db/db_connect.php';

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$conn = new mysqli($config['host'], $config['username'], $config['password'], $config['dbname']);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!$conn->set_charset("utf8mb4")) {
    die("Error loading character set utf8mb4: " . $conn->error);
}

$recordCreated = false;
$lifeEventCreated = false;
$goalCreated = false;
$mediaCreated = false;

// Fetch profile picture
$profile_picture = 'uploads/profile/default.png';

// Insert Diary Entry
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['diary_submit'])) {
    // CSRF token check
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("Invalid CSRF token");
    }

    $mood_emoji = $_POST['mood_emoji'];
    $text_entry = $_POST['text_entry'];

    $stmt = $conn->prepare("INSERT INTO diary_entries (mood_emoji, text_entry) VALUES (?, ?)");
    $stmt->bind_param("ss", $mood_emoji, $text_entry);

    if ($stmt->execute()) {
        $recordCreated = true;
    } else {
        echo "Error: " . $stmt->error;
    }
    $stmt->close();
}

// Insert Life Event
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['life_event_submit'])) {
        if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("Invalid CSRF token");
    }

    $category = $_POST['event_category'];
    $description = $_POST['event_description'];
    $event_date = $_POST['event_date'];

    $stmt = $conn->prepare("INSERT INTO life_events (category, description, event_date) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $category, $description, $event_date);

    if ($stmt->execute()) {
        $lifeEventCreated = true;
    } else {
        echo "Error: " . $stmt->error;
    }
    $stmt->close();
}

// Insert Daily Goal
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['goal_submit'])) {
        if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("Invalid CSRF token");
    }

    $goal = trim($_POST['goal']);
    $category = $_POST['category'];
    $deadline = isset($_POST['deadline']) && !empty($_POST['deadline']) ? $_POST['deadline'] : NULL; // Handle optional deadline

       if (empty($goal)) {
        $goalError = "Goal cannot be empty.";
    } elseif (!in_array($category, ['work', 'personal', 'health', 'learning'])) {
        $goalError = "Invalid category selected.";
    } else {
        $stmt = $conn->prepare("INSERT INTO daily_goals (goal, category, deadline) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $goal, $category, $deadline);

        if ($stmt->execute()) {
            $goalCreated = true;
        } else {
            echo "Error: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Insert Media Entry
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['media_submit'])) {
    // CSRF token check
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die("Invalid CSRF token");
    }

    // Validate and process the uploaded image
    if (isset($_FILES['media_image']) && $_FILES['media_image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['media_image']['name'];
        $filetype = $_FILES['media_image']['type'];
        $filesize = $_FILES['media_image']['size'];

                $ext = pathinfo($filename, PATHINFO_EXTENSION);
        if (!in_array(strtolower($ext), $allowed)) {
            echo "Error: Please select a valid file format.";
            exit();
        }

                if ($filesize > 5 * 1024 * 1024) {
            echo "Error: File size is larger than the allowed limit.";
            exit();
        }

               $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($_FILES['media_image']['tmp_name']);
        $allowed_mime = ['image/jpeg', 'image/png', 'image/gif'];
        if (!in_array($mime, $allowed_mime)) {
            echo "Error: Please select a valid image file.";
            exit();
        }

         $new_filename = uniqid() . "." . $ext;
        $upload_dir = 'uploads/media/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        $destination = $upload_dir . $new_filename;

        // Resize the image to fit within 840x530 while maintaining aspect ratio
        list($width, $height) = getimagesize($_FILES['media_image']['tmp_name']);
        $target_width = 840;
        $target_height = 530;

               $original_aspect = $width / $height;
        $target_aspect = $target_width / $target_height;

               if ($original_aspect > $target_aspect) {
            // Image is wider than target aspect ratio
            $new_width = $target_width;
            $new_height = intval($target_width / $original_aspect);
        } else {
            // Image is taller than target aspect ratio
            $new_height = $target_height;
            $new_width = intval($target_height * $original_aspect);
        }

                $image_p = imagecreatetruecolor($target_width, $target_height);

        // Fill the background with white
        $white = imagecolorallocate($image_p, 255, 255, 255);
        imagefill($image_p, 0, 0, $white);

               if ($mime == 'image/png' || $mime == 'image/gif') {
            imagecolortransparent($image_p, imagecolorallocatealpha($image_p, 0, 0, 0, 127));
            imagealphablending($image_p, false);
            imagesavealpha($image_p, true);
        }

               switch ($mime) {
            case 'image/jpeg':
                $image = imagecreatefromjpeg($_FILES['media_image']['tmp_name']);
                break;
            case 'image/png':
                $image = imagecreatefrompng($_FILES['media_image']['tmp_name']);
                break;
            case 'image/gif':
                $image = imagecreatefromgif($_FILES['media_image']['tmp_name']);
                break;
            default:
                echo "Error: Unsupported image format.";
                exit();
        }

        // Copy and resize the image with centering
        $x = intval(($target_width - $new_width) / 2);
        $y = intval(($target_height - $new_height) / 2);
        imagecopyresampled($image_p, $image, $x, $y, 0, 0, $new_width, $new_height, $width, $height);

        // Save the resized image
        switch ($mime) {
            case 'image/jpeg':
                imagejpeg($image_p, $destination, 90); // Adjust quality as needed
                break;
            case 'image/png':
                imagepng($image_p, $destination);
                break;
            case 'image/gif':
                imagegif($image_p, $destination);
                break;
        }

        imagedestroy($image_p);
        imagedestroy($image);

        // Get description and place
        $description = trim($_POST['media_description']);
        $place = trim($_POST['media_place']);
        $place_icon = ''; // Determine based on place or user input

                $place_icons = [
            'home' => 'fas fa-home',
            'park' => 'fas fa-tree',
            'beach' => 'fas fa-umbrella-beach',
            'mountain' => 'fas fa-mountain',
            'city' => 'fas fa-city',
            'other' => 'fas fa-map-marker-alt'
        ];

        // Default icon
        $place_icon = 'fas fa-map-marker-alt';
        foreach ($place_icons as $key => $icon_class) {
            if (stripos($place, $key) !== false) {
                $place_icon = $icon_class;
                break;
            }
        }

                $stmt = $conn->prepare("INSERT INTO media_entries (image_path, description, place, place_icon) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $destination, $description, $place, $place_icon);

        if ($stmt->execute()) {
            $mediaCreated = true;
        } else {
            echo "Error: " . $stmt->error;
        }
        $stmt->close();
    } else {
        echo "Error: " . $_FILES['media_image']['error'];
    }
}

// Update Goal Progress via AJAX
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_goal_progress'])) {
    // CSRF token check
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        echo json_encode(["status" => "error", "message" => "Invalid CSRF token"]);
        exit();
    }

    $goal_id = intval($_POST['goal_id']);
    $progress = intval($_POST['progress']);

    // Validate progress
    if ($progress < 0 || $progress > 100) {
        echo json_encode(["status" => "error", "message" => "Invalid progress value"]);
        exit();
    }

    $stmt = $conn->prepare("UPDATE daily_goals SET progress = ? WHERE id = ?");
    $stmt->bind_param("ii", $progress, $goal_id);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Progress updated"]);
    } else {
        echo json_encode(["status" => "error", "message" => $stmt->error]);
    }
    $stmt->close();
    exit();
}

function stringToEmoji($text) {
    $emojis = [
        'o/'         => '👋',
        '</3'        => '💔',
        '<3'         => '💗',
        '8-D'        => '😁',
        '8D'         => '😁',
        ':-D'        => '😁',
        '=-3'        => '😁',
        '=-D'        => '😁',
        '=3'         => '😁',
        '=D'         => '😁',
        'B^D'        => '😁',
        'X-D'        => '😁',
        'XD'         => '😁',
        'x-D'        => '😁',
        'xD'         => '😁',
        ':\')'       => '😂',
        ':\'-)'      => '😂',
        ':-))'       => '😃',
        '8)'         => '😄',
        ':)'         => '😄',
        ':-)'        => '😄',
        ':3'         => '😄',
        ':D'         => '😄',
        ':]'         => '😄',
        ':^)'        => '😄',
        ':c)'        => '😄',
        ':o)'        => '😄',
        ':}'         => '😄',
        ':っ)'        => '😄',
        '=)'         => '😄',
        '=]'         => '😄',
        '0:)'        => '😇',
        '0:-)'       => '😇',
        '0:-3'       => '😇',
        '0:3'        => '😇',
        '0;^)'       => '😇',
        'O:-)'       => '😇',
        '3:)'        => '😈',
        '3:-)'       => '😈',
        '}:)'        => '😈',
        '}:-)'       => '😈',
        '*)'         => '😉',
        '*-)'        => '😉',
        ':-,'        => '😉',
        ';)'         => '😉',
        ';-)'        => '😉',
        ';-]'        => '😉',
        ';D'         => '😉',
        ';]'         => '😉',
        ';^)'        => '😉',
        ':-|'        => '😐',
        ':|'         => '😐',
        ':('         => '😒',
        ':-('        => '😒',
        ':-<'        => '😒',
        ':-['        => '😒',
        ':-c'        => '😒',
        ':<'         => '😒',
        ':['         => '😒',
        ':c'         => '😒',
        ':{'         => '😒',
        ':っC'        => '😒',
        '%)'         => '😖',
        '%-)'        => '😖',
        ':-P'        => '😜',
        ':-b'        => '😜',
        ':-p'        => '😜',
        ':-Þ'        => '😜',
        ':-þ'        => '😜',
        ':P'         => '😜',
        ':b'         => '😜',
        ':p'         => '😜',
        ':Þ'         => '😜',
        ':þ'         => '😜',
        ';('         => '😜',
        '=p'         => '😜',
        'X-P'        => '😜',
        'd:'         => '😜',
        'x-p'        => '😜',
        ':-||'       => '😠',
        ':@'         => '😠',
        ':-.'        => '😡',
        ':-/'        => '😡',
        ':/'         => '😡',
        ':L'         => '😡',
        ':S'         => '😡',
        ':\\'        => '😡',
        '=/'         => '😡',
        '=L'         => '😡',
        '=\\'        => '😡',
        ':\'('       => '😢',
        ':\'-('      => '😢',
        '^5'         => '😤',
        '^<_<'       => '😤',
        'o/\\o'      => '😤',
        '|-O'        => '😫',
        '|;-)'       => '😫',
        ':###..'     => '😰',
        ':-###..'    => '😰',
        'D-\':'      => '😱',
        'D8'         => '😱',
        'D:'         => '😱',
        'D:<'        => '😱',
        'D;'         => '😱',
        'D='         => '😱',
        'DX'         => '😱',
        'v.v'        => '😱',
        '8-0'        => '😲',
        ':-O'        => '😲',
        ':-o'        => '😲',
        ':O'         => '😲',
        ':o'         => '😲',
        'O-O'        => '😲',
        'O_O'        => '😲',
        'O_o'        => '😲',
        'o-o'        => '😲',
        'o_O'        => '😲',
        'o_o'        => '😲',
        ':$'         => '😳',
        '#-)'        => '😵',
        ':#'         => '😶',
        ':&'         => '😶',
        ':-#'        => '😶',
        ':-&'        => '😶',
        ':-X'        => '😶',
        ':X'         => '😶',
        ':-J'        => '😼',
        ':*'         => '😽',
        ':^*'        => '😽',
        'ಠ_ಠ'        => '🙅',
        '*\\0/*'     => '🙆',
        '\\o/'       => '🙆',
        ':>'         => '😄',
        '>.<'        => '😡',
        '>:('        => '😠',
        '>:)'        => '😈',
        '>:-)'       => '😈',
        '>:/'        => '😡',
        '>:O'        => '😲',
        '>:P'        => '😜',
        '>:['        => '😒',
        '>:\\'       => '😡',
        '>;)'        => '😈',
        '>_>^'       => '😤',

    ];

    foreach ($emojis as $ascii => $emoji) {
        $text = str_replace($ascii, $emoji, $text);
    }

    return $text;
}

// Compute sentiment percentages
$positive_emojis = ['😊', '😂', '❤', '🥳', '😎'];
$neutral_emojis = ['🤔', '🙄'];
$negative_emojis = ['😔', '😡', '💔'];

$positive_count = 0;
$neutral_count = 0;
$negative_count = 0;

$sentiment_result = $conn->query("SELECT mood_emoji FROM diary_entries");

if ($sentiment_result) {
    while ($row = $sentiment_result->fetch_assoc()) {
        $emoji = $row['mood_emoji'];
        if (in_array($emoji, $positive_emojis)) {
            $positive_count++;
        } elseif (in_array($emoji, $negative_emojis)) {
            $negative_count++;
        } else {
            $neutral_count++;
        }
    }
}

$total_sentiment_entries = $positive_count + $neutral_count + $negative_count;

if ($total_sentiment_entries > 0) {
    $positive_percentage = round(($positive_count / $total_sentiment_entries) * 100);
    $neutral_percentage = round(($neutral_count / $total_sentiment_entries) * 100);
    $negative_percentage = round(($negative_count / $total_sentiment_entries) * 100);
} else {
    $positive_percentage = 0;
    $neutral_percentage = 0;
    $negative_percentage = 0;
}

// Fetch total number of diary entries, life events, goals, and media entries
$total_diary_entries = $conn->query("SELECT COUNT(*) FROM diary_entries")->fetch_row()[0];
$total_life_events = $conn->query("SELECT COUNT(*) FROM life_events")->fetch_row()[0];
$total_goals = $conn->query("SELECT COUNT(*) FROM daily_goals")->fetch_row()[0];
$total_media = $conn->query("SELECT COUNT(*) FROM media_entries")->fetch_row()[0];
$total_entries = $total_diary_entries + $total_life_events + $total_goals + $total_media;

// Pagination logic
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$num_results_on_page = 5;
$offset = ($page - 1) * $num_results_on_page;

$offset = intval($offset);
$limit = intval($num_results_on_page);

$query = "
    SELECT * FROM (
        SELECT 'diary' AS type, id, mood_emoji AS mood, text_entry AS content, entry_date AS date_field, NULL AS category, NULL AS description, NULL AS progress, NULL AS deadline, NULL AS image_path, NULL AS place, NULL AS place_icon
        FROM diary_entries
        UNION ALL
        SELECT 'life_event' AS type, id, NULL AS mood, NULL AS content, event_date AS date_field, category, description, NULL AS progress, NULL AS deadline, NULL AS image_path, NULL AS place, NULL AS place_icon
        FROM life_events
        UNION ALL
        SELECT 'goal' AS type, id, NULL AS mood, goal AS content, created_at AS date_field, category, NULL AS description, progress, deadline, NULL AS image_path, NULL AS place, NULL AS place_icon
        FROM daily_goals
        UNION ALL
        SELECT 'media' AS type, id, NULL AS mood, description AS content, upload_date AS date_field, NULL AS category, NULL AS description, NULL AS progress, NULL AS deadline, image_path, place, place_icon
        FROM media_entries
    ) AS combined_entries
    ORDER BY date_field DESC
    LIMIT $offset, $limit
";

$result = $conn->query($query);

if (!$result) {
    echo "Error: " . $conn->error;
}

// Fetch Daily Goals for display in the Status tab
$goals_result = $conn->prepare("SELECT * FROM daily_goals ORDER BY created_at DESC");
$goals_result->execute();
$goals = $goals_result->get_result();
$goals_result->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Personal Micro-Blog</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://use.fontawesome.com/releases/v5.8.1/css/all.css" rel="stylesheet">
   
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

       <style>
              body {
           margin: 0;
           font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
           background-color: #f0f4f8;
       }

        .container {
            max-width: 800px;
            margin: auto;
        }

        .tabs {
           max-width: 800px;
           margin: 20px auto 50px;
           padding: 0 20px;
       }

       /* Tab Navigation */
       .tab-nav {
           display: flex;
           overflow-x: auto;
           border-bottom: 1px solid #ddd;
           scrollbar-width: none; /* Firefox */
       }

       .tab-nav::-webkit-scrollbar {
           display: none;       }

       .tab-nav button {
           flex: none;
           padding: 15px 20px;
           margin-right: 5px;
           background: none;
           border: none;
           border-bottom: 3px solid transparent;
           font-size: 16px;
           cursor: pointer;
           transition: all 0.3s ease;
           color: #555;
           outline: none;        }

       .tab-nav button:hover {
           color: #000;
       }

       .tab-nav button.active {
           border-color: #007BFF;
           color: #007BFF;
           font-weight: bold;
	   margin-bottom:20px;
       }

       /* Tab Content */
       .tab-content {
           background: #fff;
           padding: 30px 20px;
           border-radius: 0 0 10px 10px;
           box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
       }

       .tab-content > div {
           display: none;
           animation: fadeIn 0.3s ease;
       }

       .tab-content > div.active {
           display: block;
       }

       @keyframes fadeIn {
           from { opacity: 0; transform: translateY(10px); }
           to { opacity: 1; transform: translateY(0); }
       }

       /* Responsive */
       @media (max-width: 600px) {
           .tab-nav button {
               padding: 10px 15px;
               font-size: 14px;
           }
       }

        /* Dark Mode */
                @media (prefers-color-scheme: dark) {
           body {
               background-color: #1e1e1e;
               color: #ccc;
           }

           .tab-nav {
               border-bottom-color: #444;
           }

           .tab-nav button {
               color: #aaa;
           }

           .tab-nav button.active {
               color: #fff;
               border-color: #0d6efd;
           }

           .tab-content {
               background: #2c2c2c;
               box-shadow: none;
           }
       }

                .card { text-align: left; }
        .emoji-picker {
            position: relative;
            display: inline-block;
            width: 62px;
            height: 62px;
            border: 1px solid #ccc;
            text-align: center;
            line-height: 62px;
            cursor: pointer;
            vertical-align: top;
        }
        .emoji-popup {
            position: absolute;
            top: 60px;
            left: 22%;
            transform: translateX(-50%);
            background-color: #fff;
            border: 1px solid #ddd;
            z-index: 1000;
            border-radius: 5px;
            display: none;
        }
        .text-area-container {
            display: flex;
            margin-bottom: 10px;
            position: relative;
        }
        .status-box {
            flex-grow: 1;
            margin-left: 5px;
            margin-right: 5px;
        }
        .send-button {
            width: 56px;
            height: 56px;
            background-color: #007bff;
            color: white;
            border: none;
            cursor: pointer;
            text-align: center;
            vertical-align: top;
        }
        .pagination {
            list-style-type: none;
            padding: 10px 0;
            display: inline-flex;
            justify-content: space-between;
            box-sizing: border-box;
        }
        .pagination li {
            box-sizing: border-box;
            padding-right: 10px;
        }
        .pagination li a {
            box-sizing: border-box;
            background-color: #e2e6e6;
            padding: 8px;
            text-decoration: none;
            font-size: 12px;
            font-weight: bold;
            color: #616872;
            border-radius: 4px;
        }
        .pagination li a:hover {
            background-color: #d4dada;
        }
        .pagination .currentpage a {
            background-color: #007bff;
            color: #fff;
        }
        .page-title {
            text-align: center;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Profile Picture Styles */
        .profile-picture {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 15px;
            border: 2px solid #007bff;
            position:relative;
            top:60px;
        }

        .search-container {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 20px;
        }

        .emoji-popup span {
            cursor: pointer;
            padding: 2px;
            font-size: 1.2em;
        }

        .sentiment-container {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 20px;
            flex-direction: column;
        }
        .sentiment-label {
            margin-bottom: 5px;
            font-weight: bold;
            font-size: 1.2em;
        }
        .progress-bar-custom {
            font-family: monospace;
            font-size: 1.5em;
            letter-spacing: 2px;
        }
        .filled {
            color: green;
        }
        .filled.mixed {
            color: orange;
        }
        .filled.negative {
            color: red;
        }
        .empty {
            color: lightgray;
        }
        .percentage-label {
            margin-top: 5px;
            font-size: 1em;
        }
        .neutral {
            color: orange;
        }
        .negative {
            color: red;
        }
        .status-icons {
            position: absolute;
            top: 10px;
            right: 10px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .status-icons i {
            cursor: pointer;
            font-size: 1.2em;
            color: #555;
            transition: color 0.3s;
        }

        .status-icons i:hover {
            color: #007bff;
        }
        #footer {
            display: table;
            text-align: center;
            margin-left: auto;
            margin-right: auto;
        }

        /* Life Events Timeline Styles */
        .timeline {
            display: flex;
            flex-direction: column;
            gap: 20px;
            max-width: 600px;
            margin: 0 auto;
        }
        .event {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 20px;
            border-radius: 8px;
            width: 100%;
            margin-bottom: 10px;
        }
        
        .event:last-child {
            margin-bottom: 15px;
        }
        .event-icon {
            margin-right: 15px;
            font-size: 24px;
            color:blue;
        }
        .event-content {
            flex-grow: 1;
        }
        .event-date {
            font-size: 0.9em;
            color: #555;
            margin-left: 20px;
        }
        /* Event Type Styles */
        .milestone {
            background-color: #d9e7ff;
        }
        .achievement {
            background-color: #d4f4dd;
        }
        .health {
            background-color: #e0ffd6;
        }
        .relationship {
            background-color: #f5e0ff;
        }
        .travel {
            background-color: #fff5d9;
        }
        .loss {
            background-color: #ececec;
        }
        .celebration {
            background-color: #fff5d4;
        }
        .unexpected {
            background-color: #fbe7e7;
        }

        /* Category Button Styles */
        .category-btn {
            margin: 2px;
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            background-color: #f0f0f0;
            transition: background-color 0.3s;
        }
        .category-btn.active {
            background-color: #007bff;
            color: #fff;
        }

        /* Goal Styles */
        .goal-container {
            max-width: 600px;
            margin: 0 auto;
        }
        .goal-card {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 15px;
            padding: 10px 15px;
            transition: background-color 0.3s ease;
        }
        .goal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 5px;
        }
        .goal-title, .goal-deadline {
            font-size: 1em;
        }
        .goal-category {
            font-size: 0.85em;
            color: #888;
            margin-bottom: 8px;
        }
        .goal-progress-bar {
            height: 8px;
            border-radius: 4px;
            background-color: #e0e0e0;
            margin-bottom: 10px;
        }
        .goal-progress {
            height: 100%;
            border-radius: 4px;
            background-color: #4caf50;
            transition: width 0.3s ease;
        }
        .goal-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .progress-label {
            font-size: 0.85em;
            color: #666;
        }
        .goal-slider {
            width: 50%;
        }

        /* Background color based on progress */
        .goal-card.low {
            background-color: #ffe5e5;
        }
        .goal-card.mid {
            background-color: #fff9e5;
        }
        .goal-card.high {
            background-color: #e5ffe5;
        }

        /* Media Entry Styles */
        .media-entry {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            display: flex;
            flex-direction: column;
            background-color: #f9f9f9;
        }
        .media-entry img {
            width: 840px;
            max-width: 100%;
            height: 530px;
            object-fit: cover;
            border-radius: 8px;
            margin-bottom: 10px;
        }
        .media-entry .media-info {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .media-entry .media-description {
            font-size: 1em;
            color: #333;
        }
        .media-entry .media-place {
            font-size: 1em;
            color: #555;
            display: flex;
            align-items: center;
        }
        .media-entry .media-place i {
            margin-right: 5px;
            color: #007bff;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <!-- Profile Picture and Page Title --> <img src="uploads/profile/default.png" alt="Profile Picture" class="profile-picture">
        <div class="page-title d-flex align-items-center justify-content-center">
            <?php if ($profile_picture && file_exists($profile_picture)): ?>
                           <?php else: ?>
               
            <?php endif; ?>
            <h1>Personal Micro-Blog</h1>
        </div>
        
        <!-- Custom Sentiment Progress Bar -->
        <div class="sentiment-container">
            <div class="sentiment-label">Overall Mood</div>
            <div class="progress-bar-custom">
                <?php
                // Define total blocks
                $total_blocks = 10;

                // Calculate filled blocks based on positive percentage
                $filled_blocks = round(($positive_percentage / 100) * $total_blocks); // Each block represents 10%
                $filled_blocks = max(0, min($filled_blocks, $total_blocks)); // Ensure within 0-10

                // Remaining blocks
                $empty_blocks = $total_blocks - $filled_blocks;

                // Decide color class based on overall sentiment
                if ($positive_percentage >= 60) {
                    $fill_class = 'filled';
                } elseif ($positive_percentage >= 30) {
                    $fill_class = 'filled mixed';
                } else {
                    $fill_class = 'filled negative';
                }

                // Render filled blocks
                for ($i = 0; $i < $filled_blocks; $i++) {
                    echo "<span class=\"$fill_class\">■</span>";
                }

                // Render empty blocks
                for ($i = 0; $i < $empty_blocks; $i++) {
                    echo "<span class=\"empty\">▢</span>";
                }
                ?>
            </div>
            <div class="percentage-label"><?= $positive_percentage ?>% Positive</div>
        </div>

        <!-- New Tab Design -->
        <div class="tabs">
            <div class="tab-nav">
                <button class="active" data-tab="Status">📊 Thoughts</button>
                <button data-tab="LifeEvents">🎯 Life Events</button>
                <button data-tab="DailyGoals">✅ Daily Goals</button>
                <button data-tab="Media">📷 Media</button>
            </div>
            <div class="tab-content">
                <!-- Tab Content: General Status Update -->
                <div id="Status" class="active">
                    <form action="" method="post" onsubmit="return validateForm(event)">
                        <div class="text-area-container">
                            <div id="emoji-picker" class="emoji-picker">🤔</div>
                            <textarea name="text_entry" class="form-control status-box" maxlength="200" placeholder="How are you feeling today?" required></textarea>
                            <button type="submit" name="diary_submit" class="btn btn-primary send-button">Send</button>
                            <div id="emoji-popup" class="emoji-popup">
                                <span onclick="selectEmoji('😊')">😊</span>
                                <span onclick="selectEmoji('😔')">😔</span>
                                <span onclick="selectEmoji('😂')">😂</span>
                                <span onclick="selectEmoji('❤')">❤</span>
                                <span onclick="selectEmoji('😡')">😡</span>
                                <span onclick="selectEmoji('🥳')">🥳</span>
                                <span onclick="selectEmoji('💔')">💔</span>
                                <span onclick="selectEmoji('😎')">😎</span>
                                <span onclick="selectEmoji('🤔')">🤔</span>
                                <span onclick="selectEmoji('🙄')">🙄</span>
                            </div>
                        </div>
                        <input type="hidden" id="selected-emoji" name="mood_emoji" value="🤔">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    </form>
                    <hr>
                    <!-- Combined Entries Section -->
                    <div class="container mt-5">
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <?php if ($row['type'] == 'diary'): ?>
                                <!-- Display Diary Entry -->
                                <div class="card mb-3 position-relative">
                                    <div class="card-body">
                                        <h3 class="card-title"><?= date("l, d F Y H:i", strtotime($row['date_field'])) ?></h3>
                                        <p class="card-text">
                                            <span><?= htmlspecialchars($row['mood']) ?></span>
                                            <span><?= htmlspecialchars(stringToEmoji($row['content'])) ?></span>
                                        </p>
                                        <!-- Icons Container -->
                                        <div class="status-icons">
                                            <i class="fas fa-edit refine-icon" data-id="<?= $row['id'] ?>" title="Ask AI to refine your status"></i>
                                            <i class="fas fa-image generate-image-icon" data-id="<?= $row['id'] ?>" title="Generate image from status"></i>
                                            <i class="fas fa-share-alt"></i>
                                        </div>
                                    </div>
                                </div>
                            <?php elseif ($row['type'] == 'life_event'): ?>
                                <!-- Display Life Event Using Timeline Design -->
                                <div class="event <?= strtolower($row['category']) ?>">
                                    <i class="fas fa-flag event-icon"></i>
                                    <div class="event-content">
                                        <strong><?= htmlspecialchars(ucfirst($row['category'])) ?></strong> - <?= htmlspecialchars($row['description']) ?>
                                    </div>
                                    <div class="event-date"><?= date("d F, Y", strtotime($row['date_field'])) ?></div>
                                </div>
                            <?php elseif ($row['type'] == 'goal'): ?>
                                <!-- Display Goal -->
                                <?php
                                    // Determine the class based on progress
                                    $progress = intval($row['progress']);
                                    if ($progress < 30) {
                                        $progress_class = 'low';
                                    } elseif ($progress < 70) {
                                        $progress_class = 'mid';
                                    } else {
                                        $progress_class = 'high';
                                    }

                                    // Handle deadline
                                    $has_deadline = isset($row['deadline']) && !empty($row['deadline']);
                                    $formatted_deadline = $has_deadline ? date("d F Y", strtotime($row['deadline'])) : '';
                                ?>
                                <div class="goal-card <?= $progress_class ?>" data-goal-id="<?= $row['id'] ?>" data-progress="<?= $row['progress'] ?>">
                                    <div class="goal-header">
                                        <span class="goal-title"><strong>Daily Goal:</strong> <?= htmlspecialchars($row['content']) ?></span>
                                        <?php if ($has_deadline): ?>
                                            <span class="goal-deadline"><strong>Deadline:</strong> <?= $formatted_deadline ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="goal-category"><small><strong>Category:</strong> <?= ucfirst($row['category']) ?></small></div>
                                    <div class="goal-progress-bar">
                                        <div class="goal-progress" style="width: <?= $row['progress'] ?>%;"></div>
                                    </div>
                                    <div class="goal-footer">
                                        <span class="progress-label"><small><strong>Progress:</strong> <?= $row['progress'] ?>%</small></span>
                                        <input type="range" class="goal-slider form-control" min="0" max="100" value="<?= $row['progress'] ?>" oninput="updateGoalProgress(this)" data-goal-id="<?= $row['id'] ?>">
                                    </div>
                                </div>
                            <?php elseif ($row['type'] == 'media'): ?>
                                <!-- Display Media Entry -->
                                <div class="media-entry">
                                    <img src="<?= htmlspecialchars($row['image_path']) ?>" alt="Media Image">
                                    <div class="media-info">
                                        <div class="media-description"><?= htmlspecialchars($row['content']) ?></div>
                                        <div class="media-place"><i class="<?= htmlspecialchars($row['place_icon']) ?>"></i> <?= htmlspecialchars($row['place']) ?></div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php endwhile; ?>
                    </div>
                </div>

                <!-- Tab Content: Life Events -->
                <div id="LifeEvents">
                    <!-- Life Event Entry Form -->
                    <h3 class="mt-4">Add a New Life Event</h3>
                    <form action="" method="post">
                        <div class="form-group">
                            <label for="event_description">Description</label>
                            <textarea id="event_description" name="event_description" class="form-control" rows="3" maxlength="200" placeholder="Describe your life event..." required></textarea>
                        </div>
                        <div class="form-group">
                            <label>Select Category:</label><br>
                            <button type="button" class="category-btn" data-category="Milestone">Milestone</button>
                            <button type="button" class="category-btn" data-category="Achievement">Achievement</button>
                            <button type="button" class="category-btn" data-category="Health">Health</button>
                            <button type="button" class="category-btn" data-category="Relationship">Relationship</button>
                            <button type="button" class="category-btn" data-category="Travel">Travel</button>
                            <button type="button" class="category-btn" data-category="Loss">Loss</button>
                            <button type="button" class="category-btn" data-category="Celebration">Celebration</button>
                            <button type="button" class="category-btn" data-category="Unexpected">Unexpected</button>
                            <!-- Add more categories as needed -->
                        </div>
                        <input type="hidden" id="selected_category" name="event_category" required>
                        <div class="form-group">
                            <label for="event_date">Event Date:</label>
                            <input type="date" id="event_date" name="event_date" class="form-control" required>
                        </div>
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <button type="submit" name="life_event_submit" class="btn btn-success">Add Event</button>
                    </form>
                </div>

                <!-- Tab Content: Daily Goals -->
                <div id="DailyGoals">
                    <h3 class="mt-4">Add a New Daily Goal</h3>
                    <div class="form-container">
                        <form action="" method="post" onsubmit="return validateGoalForm(event)">
                            <div class="form-group">
                                <label for="goal">Goal</label>
                                <input type="text" id="goal" name="goal" class="form-control" placeholder="Enter your goal..." required>
                            </div>
                            
                            <div class="form-group">
                                <label for="category">Category</label>
                                <select id="category" name="category" class="form-control" required>
                                    <option value="work">Work</option>
                                    <option value="personal">Personal</option>
                                    <option value="health">Health</option>
                                    <option value="learning">Learning</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="deadline">Deadline (optional)</label>
                                <input type="date" id="deadline" name="deadline" class="form-control">
                            </div>

                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                            <button type="submit" name="goal_submit" class="btn btn-success">Add Goal</button>
                        </form>
                    </div>
                </div>

                <!-- Tab Content: Media -->
                <div id="Media">
                    <!-- Media Entry Form -->
                    <h3 class="mt-4">Add a New Media Entry</h3>
                    <form action="" method="post" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="media_image">Upload Image <i class="fas fa-camera"></i></label>
                            <input type="file" id="media_image" name="media_image" class="form-control-file" accept="image/*" required>
                        </div>
                        <div class="form-group">
                            <label for="media_description">Description</label>
                            <textarea id="media_description" name="media_description" class="form-control" rows="3" maxlength="200" placeholder="Enter a brief description..." required></textarea>
                        </div>
                        <div class="form-group">
                            <label for="media_place">Place</label>
                            <input type="text" id="media_place" name="media_place" class="form-control" placeholder="e.g., Paris, France" required>
                        </div>
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <button type="submit" name="media_submit" class="btn btn-success">Upload Image</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Pagination for Combined Entries -->
        <?php if (ceil($total_entries / $num_results_on_page) > 0): ?>
            <div id="footer">
                <ul class="pagination">
                    <?php if ($page > 1): ?>
                        <li class="prev"><a href="?page=<?php echo $page-1 ?>">Prev</a></li>
                    <?php endif; ?>

                    <?php if ($page > 3): ?>
                        <li class="start"><a href="?page=1">1</a></li>
                        <li class="dots">...</li>
                    <?php endif; ?>

                    <?php if ($page-2 > 0): ?><li class="page"><a href="?page=<?php echo $page-2 ?>"><?php echo $page-2 ?></a></li><?php endif; ?>
                    <?php if ($page-1 > 0): ?><li class="page"><a href="?page=<?php echo $page-1 ?>"><?php echo $page-1 ?></a></li><?php endif; ?>

                    <li class="currentpage"><a href="?page=<?php echo $page ?>"><?php echo $page ?></a></li>

                    <?php if ($page+1 < ceil($total_entries / $num_results_on_page)+1): ?><li class="page"><a href="?page=<?php echo $page+1 ?>"><?php echo $page+1 ?></a></li><?php endif; ?>
                    <?php if ($page+2 < ceil($total_entries / $num_results_on_page)+1): ?><li class="page"><a href="?page=<?php echo $page+2 ?>"><?php echo $page+2 ?></a></li><?php endif; ?>

                    <?php if ($page < ceil($total_entries / $num_results_on_page)): ?>
                        <li class="next"><a href="?page=<?php echo $page+1 ?>">Next</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        <?php endif; ?>
<!-- JavaScript Dependencies and Custom Scripts -->

<script src="https://code.jquery.com/jquery-3.3.1.min.js"></script>

<!-- Popper.js -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"></script>

<!-- Bootstrap JS -->
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js"></script>

<!-- Your Custom Script -->
<script src="script.js"></script>

        <!-- Success Modals -->
        <?php if ($recordCreated): ?>
        <div class="modal fade" id="successModal" tabindex="-1" role="dialog" aria-labelledby="successModalLabel" aria-hidden="true">
          <div class="modal-dialog" role="document">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="successModalLabel">Success</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
                </button>
              </div>
              <div class="modal-body">
                Your status update has been posted successfully!
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
              </div>
            </div>
          </div>
        </div>
        <?php endif; ?>

        <?php if ($lifeEventCreated): ?>
        <div class="modal fade" id="lifeEventSuccessModal" tabindex="-1" role="dialog" aria-labelledby="lifeEventSuccessModalLabel" aria-hidden="true">
          <div class="modal-dialog" role="document">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="lifeEventSuccessModalLabel">Success</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">&times;</span>
                </button>
              </div>
              <div class="modal-body">
                Your life event has been added successfully!
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
              </div>
            </div>
          </div>
        </div>
        <?php endif; ?>

        <?php if ($goalCreated): ?>
        <div class="modal fade" id="goalSuccessModal" tabindex="-1" role="dialog" aria-labelledby="goalSuccessModalLabel" aria-hidden="true">
          <div class="modal-dialog" role="document">
            <div class="modal-content">
              <div class="modal-header"> 
                <h5 class="modal-title" id="goalSuccessModalLabel">Success</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"> 
                  <span aria-hidden="true">&times;</span>
                </button>
              </div>
              <div class="modal-body">
                Your daily goal has been added successfully!
              </div>
              <div class="modal-footer"> 
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button> 
              </div>
            </div>
          </div>
        </div>
        <?php endif; ?>

        <?php if ($mediaCreated): ?>
        <div class="modal fade" id="mediaSuccessModal" tabindex="-1" role="dialog" aria-labelledby="mediaSuccessModalLabel" aria-hidden="true">
          <div class="modal-dialog" role="document">
            <div class="modal-content">
              <div class="modal-header"> 
                <h5 class="modal-title" id="mediaSuccessModalLabel">Success</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"> 
                  <span aria-hidden="true">&times;</span>
                </button>
              </div>
              <div class="modal-body">
                Your media has been uploaded successfully!
              </div>
              <div class="modal-footer"> 
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button> 
              </div>
            </div>
          </div>
        </div>
        <?php endif; ?>

    </div>

    <!-- Scripts -->
    <script>
        // New Tab Functionality
        const tabs = document.querySelectorAll('.tab-nav button');
        const contents = document.querySelectorAll('.tab-content > div');

        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                // Deactivate all tabs and contents
                tabs.forEach(btn => btn.classList.remove('active'));
                contents.forEach(content => content.classList.remove('active'));

                // Activate the clicked tab and corresponding content
                tab.classList.add('active');
                document.getElementById(tab.getAttribute('data-tab')).classList.add('active');
            });
        });

        // Emoji Picker Functionality
        const emojiPicker = document.getElementById('emoji-picker');
        const emojiPopup = document.getElementById('emoji-popup');
        const selectedEmojiInput = document.getElementById('selected-emoji');

        if (emojiPicker) {
            emojiPicker.addEventListener('click', () => {
                emojiPopup.style.display = emojiPopup.style.display === 'block' ? 'none' : 'block';
            });

            function selectEmoji(emoji) {
                selectedEmojiInput.value = emoji;
                emojiPicker.textContent = emoji;
                emojiPopup.style.display = 'none';
            }

            // Close the emoji popup when clicking outside
            document.addEventListener('click', function(event) {
                if (!emojiPicker.contains(event.target) && !emojiPopup.contains(event.target)) {
                    emojiPopup.style.display = 'none';
                }
            });
        }

        // Category Button Selection for Life Events
        const categoryButtons = document.querySelectorAll('.category-btn');
        const selectedCategoryInput = document.getElementById('selected_category');

        categoryButtons.forEach(button => {
            button.addEventListener('click', () => {
                // Remove 'active' class from all buttons
                categoryButtons.forEach(btn => btn.classList.remove('active'));
                // Add 'active' class to the clicked button
                button.classList.add('active');
                // Set the selected category value
                selectedCategoryInput.value = button.getAttribute('data-category');
            });
        });

        // Validate Diary Entry Form
        function validateForm(event) {
            const emoji = document.getElementById('selected-emoji').value;
            const text = document.querySelector('textarea[name="text_entry"]').value.trim();
            if (!emoji || !text) {
                alert("Please select an emoji and enter your status.");
                event.preventDefault();
                return false;
            }
            return true;
        }

        // Validate Goal Submission Form
        function validateGoalForm(event) {
            const goal = document.getElementById('goal').value.trim();
            const category = document.getElementById('category').value;
            if (!goal) {
                alert("Please enter a goal.");
                event.preventDefault();
                return false;
            }
            if (!['work', 'personal', 'health', 'learning'].includes(category)) {
                alert("Please select a valid category.");
                event.preventDefault();
                return false;
            }
            return true;
        }

        // Update Goal Progress via AJAX
        function updateGoalProgress(slider) {
            const goalCard = slider.closest('.goal-card');
            const progressBar = goalCard.querySelector('.goal-progress');
            const progressLabel = goalCard.querySelector('.progress-label');
            const progressValue = slider.value;
            const goalId = slider.getAttribute('data-goal-id');

            progressBar.style.width = progressValue + '%';
            progressLabel.textContent = 'Progress: ' + progressValue + '%';

            // Update class based on progress for background color
            if (progressValue < 30) {
                goalCard.classList.remove('mid', 'high');
                goalCard.classList.add('low');
            } else if (progressValue < 70) {
                goalCard.classList.remove('low', 'high');
                goalCard.classList.add('mid');
            } else {
                goalCard.classList.remove('low', 'mid');
                goalCard.classList.add('high');
            }

            // Prepare URL-encoded data
            const params = new URLSearchParams();
            params.append('update_goal_progress', true);
            params.append('csrf_token', '<?= $_SESSION['csrf_token'] ?>');
            params.append('goal_id', goalId);
            params.append('progress', progressValue);

            // Send AJAX request to update progress in the database
            axios.post('', params)
            .then(function (response) {
                // Handle success or error
                if (response.data.status === 'success') {
                    console.log('Progress updated successfully.');
                } else {
                    alert('Error updating progress: ' + response.data.message);
                }
            })
            .catch(function (error) {
                console.error('Error:', error);
                alert('An error occurred while updating progress.');
            });
        }
    </script>
   
</body>
</html>
