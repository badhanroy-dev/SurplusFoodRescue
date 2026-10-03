<?php

session_start();

include "db.php";


/*
|--------------------------------------------------------------------------
| LOGIN CHECK
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['user_id'])) {

    header("Location: login.php");

    exit();

}

$user_id = (int) $_SESSION['user_id'];


/*
|--------------------------------------------------------------------------
| GET CURRENT USER
|--------------------------------------------------------------------------
*/

$user_sql = "
    SELECT
        user_id,
        full_name,
        role,
        account_status
    FROM users
    WHERE user_id = ?
    LIMIT 1
";

$user_stmt = mysqli_prepare($conn, $user_sql);

mysqli_stmt_bind_param(
    $user_stmt,
    "i",
    $user_id
);

mysqli_stmt_execute($user_stmt);

$user_result = mysqli_stmt_get_result($user_stmt);

$user = mysqli_fetch_assoc($user_result);

mysqli_stmt_close($user_stmt);


if (!$user) {

    session_destroy();

    header("Location: login.php");

    exit();

}


/*
|--------------------------------------------------------------------------
| SELECTED CONVERSATION
|--------------------------------------------------------------------------
*/

$selected_user_id = isset($_GET['user'])
    ? (int) $_GET['user']
    : 0;


/*
|--------------------------------------------------------------------------
| ACTIVE TAB
|--------------------------------------------------------------------------
*/

$active_tab = isset($_GET['tab'])
    ? $_GET['tab']
    : "All";


$allowed_tabs = [
    "All",
    "Volunteer",
    "Admin"
];


if (!in_array($active_tab, $allowed_tabs)) {

    $active_tab = "All";

}


/*
|--------------------------------------------------------------------------
| SEND MESSAGE
|--------------------------------------------------------------------------
*/

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $receiver_id = isset($_POST['receiver_id'])
        ? (int) $_POST['receiver_id']
        : 0;

    $message_text = trim(
        $_POST['message_text'] ?? ""
    );


    if (
        $receiver_id > 0 &&
        $receiver_id != $user_id &&
        $message_text != ""
    ) {

        /*
        | Check that receiver exists and is an allowed user.
        */

        $receiver_check_sql = "
            SELECT user_id
            FROM users
            WHERE user_id = ?
            AND account_status IN ('Active', 'Verified')
            LIMIT 1
        ";

        $receiver_check_stmt = mysqli_prepare(
            $conn,
            $receiver_check_sql
        );

        mysqli_stmt_bind_param(
            $receiver_check_stmt,
            "i",
            $receiver_id
        );

        mysqli_stmt_execute(
            $receiver_check_stmt
        );

        $receiver_check_result =
            mysqli_stmt_get_result(
                $receiver_check_stmt
            );

        $receiver_exists =
            mysqli_fetch_assoc(
                $receiver_check_result
            );

        mysqli_stmt_close(
            $receiver_check_stmt
        );


        if ($receiver_exists) {

            /*
            | Get the latest delivery_id between
            | the two users, if one exists.
            */

            $delivery_sql = "
                SELECT delivery_id
                FROM messages
                WHERE
                    (
                        sender_id = ?
                        AND receiver_id = ?
                    )
                    OR
                    (
                        sender_id = ?
                        AND receiver_id = ?
                    )
                AND delivery_id IS NOT NULL
                ORDER BY sent_at DESC, message_id DESC
                LIMIT 1
            ";

            $delivery_stmt = mysqli_prepare(
                $conn,
                $delivery_sql
            );

            mysqli_stmt_bind_param(
                $delivery_stmt,
                "iiii",
                $user_id,
                $receiver_id,
                $receiver_id,
                $user_id
            );

            mysqli_stmt_execute(
                $delivery_stmt
            );

            $delivery_result =
                mysqli_stmt_get_result(
                    $delivery_stmt
                );

            $delivery_row =
                mysqli_fetch_assoc(
                    $delivery_result
                );

            mysqli_stmt_close(
                $delivery_stmt
            );


            $delivery_id = null;


            if ($delivery_row) {

                $delivery_id =
                    (int) $delivery_row['delivery_id'];

            }


            /*
            | Insert the new message.
            */

            if ($delivery_id !== null) {

                $insert_sql = "
                    INSERT INTO messages
                    (
                        sender_id,
                        receiver_id,
                        delivery_id,
                        message_text,
                        is_read
                    )
                    VALUES
                    (
                        ?,
                        ?,
                        ?,
                        ?,
                        0
                    )
                ";

                $insert_stmt = mysqli_prepare(
                    $conn,
                    $insert_sql
                );

                mysqli_stmt_bind_param(
                    $insert_stmt,
                    "iiis",
                    $user_id,
                    $receiver_id,
                    $delivery_id,
                    $message_text
                );

            } else {

                $insert_sql = "
                    INSERT INTO messages
                    (
                        sender_id,
                        receiver_id,
                        message_text,
                        is_read
                    )
                    VALUES
                    (
                        ?,
                        ?,
                        ?,
                        0
                    )
                ";

                $insert_stmt = mysqli_prepare(
                    $conn,
                    $insert_sql
                );

                mysqli_stmt_bind_param(
                    $insert_stmt,
                    "iis",
                    $user_id,
                    $receiver_id,
                    $message_text
                );

            }


            mysqli_stmt_execute($insert_stmt);

            mysqli_stmt_close($insert_stmt);

        }

    }


    /*
    | Redirect to prevent duplicate form submission.
    */

    header(
        "Location: message.php?user="
        . $receiver_id
        . "&tab="
        . urlencode($active_tab)
    );

    exit();

}


/*
|--------------------------------------------------------------------------
| CONVERSATION LIST
|--------------------------------------------------------------------------
|
| We find the latest message for every person
| who has exchanged messages with the logged-in user.
|--------------------------------------------------------------------------
*/

$conversations = [];


$conversation_sql = "
    SELECT
        other_user.user_id,
        other_user.full_name,
        other_user.role,
        other_user.account_status,
        latest.message_text,
        latest.sent_at,
        latest.is_read,
        latest.sender_id
    FROM users AS other_user

    INNER JOIN
    (
        SELECT
            CASE
                WHEN sender_id = ?
                THEN receiver_id
                ELSE sender_id
            END AS other_user_id,

            MAX(sent_at) AS latest_sent_at

        FROM messages

        WHERE
            sender_id = ?
            OR receiver_id = ?

        GROUP BY
            CASE
                WHEN sender_id = ?
                THEN receiver_id
                ELSE sender_id
            END
    ) AS latest_message
        ON latest_message.other_user_id = other_user.user_id

    INNER JOIN messages AS latest
        ON (
            (
                latest.sender_id = ?
                AND latest.receiver_id = other_user.user_id
            )
            OR
            (
                latest.sender_id = other_user.user_id
                AND latest.receiver_id = ?
            )
        )
        AND latest.sent_at = latest_message.latest_sent_at

    WHERE 1 = 1
";


if ($active_tab === "Volunteer") {

    $conversation_sql .= "
        AND other_user.role = 'Volunteer'
    ";

} elseif ($active_tab === "Admin") {

    $conversation_sql .= "
        AND other_user.role = 'Admin'
    ";

}


$conversation_sql .= "
    ORDER BY latest.sent_at DESC
";


$conversation_stmt = mysqli_prepare(
    $conn,
    $conversation_sql
);


mysqli_stmt_bind_param(
    $conversation_stmt,
    "iiiiii",
    $user_id,
    $user_id,
    $user_id,
    $user_id,
    $user_id,
    $user_id
);


mysqli_stmt_execute(
    $conversation_stmt
);


$conversation_result =
    mysqli_stmt_get_result(
        $conversation_stmt
    );


while (
    $conversation =
    mysqli_fetch_assoc(
        $conversation_result
    )
) {

    $conversations[] = $conversation;

}


mysqli_stmt_close(
    $conversation_stmt
);


/*
|--------------------------------------------------------------------------
| IF NO USER IS SELECTED
|--------------------------------------------------------------------------
|
| Open the newest conversation automatically.
|--------------------------------------------------------------------------
*/

if (
    $selected_user_id <= 0 &&
    count($conversations) > 0
) {

    $selected_user_id =
        (int) $conversations[0]['user_id'];

}


/*
|--------------------------------------------------------------------------
| SELECTED USER INFORMATION
|--------------------------------------------------------------------------
*/

$selected_user = null;


if ($selected_user_id > 0) {

    $selected_user_sql = "
        SELECT
            user_id,
            full_name,
            role,
            account_status
        FROM users
        WHERE user_id = ?
        LIMIT 1
    ";

    $selected_user_stmt = mysqli_prepare(
        $conn,
        $selected_user_sql
    );

    mysqli_stmt_bind_param(
        $selected_user_stmt,
        "i",
        $selected_user_id
    );

    mysqli_stmt_execute(
        $selected_user_stmt
    );

    $selected_user_result =
        mysqli_stmt_get_result(
            $selected_user_stmt
        );

    $selected_user =
        mysqli_fetch_assoc(
            $selected_user_result
        );

    mysqli_stmt_close(
        $selected_user_stmt
    );

}


/*
|--------------------------------------------------------------------------
| MARK RECEIVED MESSAGES AS READ
|--------------------------------------------------------------------------
*/

if ($selected_user) {

    $read_sql = "
        UPDATE messages
        SET is_read = 1
        WHERE sender_id = ?
        AND receiver_id = ?
        AND is_read = 0
    ";

    $read_stmt = mysqli_prepare(
        $conn,
        $read_sql
    );

    mysqli_stmt_bind_param(
        $read_stmt,
        "ii",
        $selected_user_id,
        $user_id
    );

    mysqli_stmt_execute(
        $read_stmt
    );

    mysqli_stmt_close(
        $read_stmt
    );

}


/*
|--------------------------------------------------------------------------
| GET MESSAGES FOR SELECTED CONVERSATION
|--------------------------------------------------------------------------
*/

$chat_messages = [];


if ($selected_user) {

    $chat_sql = "
        SELECT
            message_id,
            sender_id,
            receiver_id,
            message_text,
            is_read,
            sent_at
        FROM messages
        WHERE
            (
                sender_id = ?
                AND receiver_id = ?
            )
            OR
            (
                sender_id = ?
                AND receiver_id = ?
            )
        ORDER BY sent_at ASC, message_id ASC
    ";

    $chat_stmt = mysqli_prepare(
        $conn,
        $chat_sql
    );

    mysqli_stmt_bind_param(
        $chat_stmt,
        "iiii",
        $user_id,
        $selected_user_id,
        $selected_user_id,
        $user_id
    );

    mysqli_stmt_execute(
        $chat_stmt
    );

    $chat_result =
        mysqli_stmt_get_result(
            $chat_stmt
        );


    while (
        $chat_message =
        mysqli_fetch_assoc(
            $chat_result
        )
    ) {

        $chat_messages[] =
            $chat_message;

    }


    mysqli_stmt_close(
        $chat_stmt
    );

}


/*
|--------------------------------------------------------------------------
| TIME FORMAT
|--------------------------------------------------------------------------
*/

function getMessageTime($date_time)
{

    $time = strtotime($date_time);


    if (!$time) {

        return "";

    }


    return date(
        "g:i A",
        $time
    );

}


function getConversationTime($date_time)
{

    $time = strtotime($date_time);


    if (!$time) {

        return "";

    }


    $today = date("Y-m-d");

    $message_date = date(
        "Y-m-d",
        $time
    );


    if ($message_date == $today) {

        return date(
            "g:i A",
            $time
        );

    }


    if (
        $message_date ==
        date(
            "Y-m-d",
            strtotime("-1 day")
        )
    ) {

        return "Yesterday";

    }


    return date(
        "M d",
        $time
    );

}

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        FoodRescue - Messages
    </title>

    <link
        rel="stylesheet"
        href="message.css"
    >

</head>


<body>


<div class="dashboard">


    <!-- SIDEBAR -->

    <aside class="sidebar">

        <div class="logo">

            <h2>
                FoodRescue
            </h2>

            <p>
                Donor Portal
            </p>

        </div>


        <nav class="menu">

            <a href="dashboard.php">
                Dashboard
            </a>

            <a href="post.php">
                Post Food
            </a>

            <a href="shedule.php">
                Pickup Requests
            </a>

            <a href="impact_summary.php">
                Impact Summary
            </a>

            <a href="mulreceipt.php">
                Receipts
            </a>

            <a
                href="message.php"
                class="active"
            >
                Messages
            </a>

            <a href="history.php">
                Donation History
            </a>

            <a href="multracking.php">
                Pickup Status
            </a>

        </nav>


        <div class="bottom-menu">

            <a href="#">
                Settings
            </a>

            <a href="logout.php">
                Logout
            </a>

        </div>

    </aside>



    <!-- MAIN -->

    <main class="main">


        <!-- HEADER -->

        <header class="topbar">

            <div class="top-icons">

                <span>
                    🔔 &nbsp; 👤
                </span>

            </div>

        </header>



        <!-- MAIN CHAT CONTENT -->

        <div class="content">

            <div class="chat-container">


                <!-- CHAT LIST -->

                <div class="chat-list">


                    <div class="chat-list-header">

                        <h2>
                            Messages
                        </h2>


                        <div class="chat-tabs">

                            <a
                                href="message.php?tab=All"
                                class="<?php echo ($active_tab == 'All') ? 'active-tab' : ''; ?>"
                            >
                                All
                            </a>


                            <a
                                href="message.php?tab=Volunteer"
                                class="<?php echo ($active_tab == 'Volunteer') ? 'active-tab' : ''; ?>"
                            >
                                Volunteer
                            </a>


                            <a
                                href="message.php?tab=Admin"
                                class="<?php echo ($active_tab == 'Admin') ? 'active-tab' : ''; ?>"
                            >
                                Admin
                            </a>

                        </div>

                    </div>



                    <!-- CONVERSATIONS -->

                    <?php if (count($conversations) > 0) { ?>


                        <?php foreach ($conversations as $conversation) { ?>


                            <a
                                href="message.php?user=<?php echo (int) $conversation['user_id']; ?>&tab=<?php echo urlencode($active_tab); ?>"
                                class="conversation <?php echo ($selected_user_id == $conversation['user_id']) ? 'selected' : ''; ?>"
                            >


                                <div class="conversation-avatar">

                                    👤

                                </div>


                                <div class="conversation-info">


                                    <div class="conversation-top">

                                        <h3>

                                            <?php

                                            echo htmlspecialchars(
                                                $conversation['full_name']
                                            );

                                            ?>

                                        </h3>


                                        <span>

                                            <?php

                                            echo htmlspecialchars(
                                                getConversationTime(
                                                    $conversation['sent_at']
                                                )
                                            );

                                            ?>

                                        </span>

                                    </div>


                                    <p>

                                        <?php

                                        echo htmlspecialchars(
                                            $conversation['message_text']
                                        );

                                        ?>

                                    </p>


                                </div>

                            </a>


                        <?php } ?>


                    <?php } else { ?>


                        <div class="conversation">

                            <div class="conversation-avatar">

                                ✉

                            </div>


                            <div class="conversation-info">

                                <div class="conversation-top">

                                    <h3>
                                        No conversations
                                    </h3>

                                </div>


                                <p>
                                    No messages found.
                                </p>

                            </div>

                        </div>


                    <?php } ?>


                </div>



                <!-- CHAT WINDOW -->

                <div class="chat-window">


                    <?php if ($selected_user) { ?>


                        <!-- CHAT HEADER -->

                        <div class="chat-header">


                            <div class="chat-user">


                                <div class="chat-avatar">

                                    👤

                                </div>


                                <div>

                                    <h3>

                                        <?php

                                        echo htmlspecialchars(
                                            $selected_user['full_name']
                                        );

                                        ?>

                                    </h3>


                                    <p>

                                        <span class="online-dot"></span>

                                        <?php

                                        if (
                                            $selected_user['account_status']
                                            == "Active"
                                            ||
                                            $selected_user['account_status']
                                            == "Verified"
                                        ) {

                                            echo "Active";

                                        } else {

                                            echo htmlspecialchars(
                                                $selected_user['account_status']
                                            );

                                        }

                                        ?>

                                    </p>

                                </div>

                            </div>

                        </div>



                        <!-- MESSAGES -->

                        <div class="messages">


                            <?php if (count($chat_messages) > 0) { ?>


                                <?php foreach ($chat_messages as $chat_message) { ?>


                                    <?php

                                    $is_sent =
                                        (
                                            (int) $chat_message['sender_id']
                                            ===
                                            $user_id
                                        );

                                    ?>


                                    <?php if (!$is_sent) { ?>


                                        <!-- RECEIVED -->

                                        <div class="message received">


                                            <div class="message-avatar">

                                                👤

                                            </div>


                                            <div>


                                                <div class="message-bubble">

                                                    <?php

                                                    echo nl2br(
                                                        htmlspecialchars(
                                                            $chat_message['message_text']
                                                        )
                                                    );

                                                    ?>

                                                </div>


                                                <span class="message-time">

                                                    <?php

                                                    echo htmlspecialchars(
                                                        getMessageTime(
                                                            $chat_message['sent_at']
                                                        )
                                                    );

                                                    ?>

                                                </span>

                                            </div>

                                        </div>


                                    <?php } else { ?>


                                        <!-- SENT -->

                                        <div class="message sent">


                                            <div>


                                                <div class="message-bubble">

                                                    <?php

                                                    echo nl2br(
                                                        htmlspecialchars(
                                                            $chat_message['message_text']
                                                        )
                                                    );

                                                    ?>

                                                </div>


                                                <span class="message-time">

                                                    <?php

                                                    echo htmlspecialchars(
                                                        getMessageTime(
                                                            $chat_message['sent_at']
                                                        )
                                                    );

                                                    ?>

                                                </span>

                                            </div>

                                        </div>


                                    <?php } ?>


                                <?php } ?>


                            <?php } else { ?>


                                <div class="message received">


                                    <div class="message-avatar">

                                        ✉

                                    </div>


                                    <div>

                                        <div class="message-bubble">

                                            No messages in this conversation yet.

                                        </div>

                                    </div>

                                </div>


                            <?php } ?>


                        </div>



                        <!-- MESSAGE INPUT -->

                        <form
                            class="message-input"
                            method="POST"
                            action="message.php?user=<?php echo $selected_user_id; ?>&tab=<?php echo urlencode($active_tab); ?>"
                        >


                            <input
                                type="text"
                                name="message_text"
                                placeholder="Type a message..."
                                autocomplete="off"
                                required
                            >


                            <input
                                type="hidden"
                                name="receiver_id"
                                value="<?php echo $selected_user_id; ?>"
                            >


                            <button
                                type="submit"
                                title="Send Message"
                            >
                                ➤
                            </button>


                        </form>


                    <?php } else { ?>


                        <div class="chat-header">

                            <div class="chat-user">

                                <div class="chat-avatar">

                                    ✉

                                </div>


                                <div>

                                    <h3>
                                        Messages
                                    </h3>

                                    <p>
                                        Select a conversation
                                    </p>

                                </div>

                            </div>

                        </div>


                        <div class="messages">

                            <div class="message received">

                                <div class="message-avatar">

                                    ✉

                                </div>


                                <div>

                                    <div class="message-bubble">

                                        You do not have any conversations yet.

                                    </div>

                                </div>

                            </div>

                        </div>


                    <?php } ?>


                </div>

            </div>

        </div>

    </main>

</div>


</body>

</html>