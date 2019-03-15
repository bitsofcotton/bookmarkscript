<?php

session_start();

define("DB_TBL_ACCOUNT", "account");
define("DB_TBL_TAG",     "tags");
define("DB_TBL_NODE",    "nodes");
define("DB_TAG_0",       1);

try {
  // php that fresh enough, we can do database queries on PDO.
  // otherwise, it's dangerous.
  // $pdo = new PDO('mysql:host=127.0.0.1:dbname=bookmark', DB_USER, DB_PASS, (PDO::MYSQL_ATTR_INIT_COMMAND=>"SET CHARACTER SET 'utf8'"));
  $pdo = new PDO('sqlite:./bookmark.db');
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
  echo "DB error.";
  // var_dump($e);
  exit;
}

function write_existing_tags($row, $check, $edit)
{
  echo '<tr>';
  if($check != false) {
    echo '<td><input class="noboarder" type="checkbox" name="' . $check . '" /></td>';
  }
  if($edit) {
    echo '<td>' . $row['title'] . '</td>';
    echo '<td>' . $row['intro'] . '</td>';
    echo '<td>' . $row['words'] . '</td>';
    echo '<td><a href="index.php?menu=tag&mode=edit&tid=' . $row['tid'] .
      '">Edit</a></td>';
    echo '<td><a href="index.php?menu=tag&mode=delete&tid=' .
      $row['tid'] . '">Delete</a></td>';
  } else {
    echo '<td>' . $row['title'] . '</td>';
    echo '<td>' . $row['intro'] . '</td>';
    echo '<td>' . $row['words'] . '</td>';
  }
  echo '</tr>';
}

function write_hierarchy($tid, $check, $newbox, $depth = 3)
{
  global $pdo;
  if($depth < 0) {
    return;
  }
  echo '<div class="hierarchy"><table>';
  try {
    $stmt = $pdo->prepare("SELECT * FROM " . DB_TBL_TAG . " WHERE " .
                          "l_parent = :tid ;");
    $stmt->execute(array(':tid' => $tid));
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach($rows as $row) {
      if($check)
        write_existing_tags($row, 'chktid_' . $row['tid'], $newbox);
      else
        write_existing_tags($row, $check, $newbox);
      if($newbox)
        echo '<tr><td colspan="5">';
      else
        echo '<tr><td colspan="3">';
      write_hierarchy($row['tid'], $check, $newbox, $depth - 1);
      echo "</td></tr>";
    }
  } catch(PDOExcept $e) {
    echo "DB error";
    exit;
  }
  if($newbox) {
    echo '<tr><form action="index.php?menu=tag&mode=new&tid=' . $tid . '" method="post">';
    echo '<td><input class="noboarder" type="text" name="title" width="7" /></td>';
    echo '<td><input class="noboarder" type="text" name="intro" width="7" /></td>';
    echo '<td><input class="noboarder" type="text" name="words" width="7" /></td>';
    echo '<td><input class="noboarder" type="submit" /></td>';
    echo '</form></tr>';
  }
  echo "</table></div>";
}

function trace_hierarchy($tid, $req, $depth = 3)
{
  global $pdo;
  if($depth < 0) {
    return "";
  }
  $tags = " ";
  try {
    $stmt = $pdo->prepare("SELECT * FROM " . DB_TBL_TAG . " WHERE " .
                          "l_parent = :tid ;");
    $stmt->execute(array(':tid' => $tid));
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach($rows as $row) {
      if(isset($req['chktid_' . $row['tid']]) &&
               $req['chktid_' . $row['tid']]) {
        $tags .= ", " . $row['tid'];
      }
      $tags .= ", " . trace_hierarchy($row['tid'], $req, $depth - 1);
    }
  } catch(PDOException $e) {
    echo "DB error.";
    exit;
  }
  return $tags . ", ";
}

  // Start index.php
if(isset($_REQUEST['menu']) && isset($_SESSION['name']) &&
   isset($_SESSION['pass']) && $_REQUEST['menu'] == 'rss') {
  echo '<?xml version="1.0" encoding="UTF-8"?>';
?>

<rss version="2.0" xmlns:dc="http://purl.org/dc/elements/1.1/">
  <channel>
    <title>Auto Generated RSS Feed</title>
    <link>http://services.limpid-intensity.info/bookmark</link>
    <language></language>
    <ttl>40</ttl>
    <description>Auto Generated RSS Feed (services.limpid-intensity.info)</description>
    
    <item>
      <title>No article now</title>
      <description>Now implementing, please wait some months to be able to work with this (some days per month).</description>
      <pubDate>N/A</pubDate>
      <guid>http://services.limpid-intensity.info/bookmark/</guid>
      <link>http://to_source.com/</link>
    </item>
    
  </channel>
</rss>
<?php
  exit;
}
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html401/
strict.dtd">

<html lang="ja">
<head>
  <title>Bookmark script</title>
  <style type="text/css">
<!--
  body#root { font-size: 12pt;
              text-align: center; }
  table.noboarder { border: 0px; font-size: 10pt; }
  tr.noboarder { border: 0px; font-size: 10pt; }
  td.noboarder { border: 0px; font-size: 10pt; }
  p#menu { font-size: 10pt;
           padding: 2px;
           margin: 2px; }
  p.comment { font-size: 9pt; }
  input.noboarder { border: 1px solid; }
  textarea.noboarder { border: 1px solid; }
  div#top_menu { border: 1px;
                 border-style: solid;
                 padding: 2px;
                 margin: 2px;
                 text-align: right; }
  div#contains { border: 1px;
                 border-style: solid;
                 padding: 2px;
                 margin: 2px;
                 text-align: left; }
  div.hierarchy { border: 0px;
                  border-style: none;
                  padding: 2px;
                  margin: 2px 2px 2px 12px;
                  text-align: left;
                  font-size: 9pt; }
-->
  </style>
  <meta http-equiv="Content-Type" content="text/html; charset=UTF-8;">
</head>

<body id="root">
<?php
  $flag = false;
  if(!isset($_SESSION['name']) or
     !isset($_SESSION['pass'])) {
    if(!isset($_POST['name']) or
       !isset($_POST['pass'])) {
      $flag = true;
    } else {
      $_SESSION['name'] = $_POST['name'];
      $_SESSION['pass'] = $_POST['name'] . $_POST['pass'];
    }
  }
  // ### index.php with no login ###
  if($flag) {
    session_destroy();
    // normal operation
?>
<div id="top_menu">
  <form action="index.php?menu=search">
  <p class="menu">
     Search: <input class="noboarder" type="text" />
     (<input type="checkbox" value="Tag"  name="tag" />Tag |
      <input type="checkbox" value="Flag" name="intro" />Intro ||
      <input type="checkbox" value="Mine" name="mine" />Mine )
    &nbsp; &nbsp;
  </p>
  </form>
</div>
<div id="contains">
  <div align="center">
   Please <?php if($flag) echo "<b>"; ?>Login<?php if($flag) echo "</b>"; ?>:
    <br/>
   <form action="index.php?menu=index" method="post">
   <table class="noboarder">
   <tr class="noboarder"><td class="noboarder">ID: </td>
     <td class="noboarder"><input class="noboarder" type="text" name="name" />
       </td></tr>
   <tr class="noboarder"><td class="noboarder">Pass: </td>
     <td class="noboarder">
       <input class="noboarder" type="password" name="pass" /></td></tr>
   <tr class="noboarder"><td class="noboarder">EMail(Only for Register): </td>
     <td class="noboarder">
       <input class="noboarder" type="text" name="email" /></td></tr>
   </table>
   <input type="submit" value="Login" />
   or check <input type="checkbox" name="register" />Register.<br/><br/>
   </form>
  </div>

  Following are randomly picked up articles.<br/>
<?php
    try {
      $stmt = $pdo->prepare("SELECT href, title, intro, tag, words FROM " .
                            DB_TBL_NODE . " ORDER BY random() LIMIT 50;");
      $stmt->execute(array());
      echo '<table>';
      while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo '<tr>';
        echo '<td><a href="' . $row['href'] . '">' . $row['title'] . '</a></td>';
        echo '<td>' . $row['intro'] . '</td>';
        $tags = explode(",", $row['tag']);
        echo '<td>';
        foreach($tags as $tag) {
          $stmt2 = $pdo->prepare("SELECT title FROM " . DB_TBL_TAG . " WHERE " .
                                 "tid = :tid LIMIT 1;");
          $stmt2->execute(array(':tid' => $tag));
          if($subresult = $stmt2->fetch(PDO::FETCH_ASSOC)) {
            echo '<a href="index.php?menu=tag&tid=' . $tag . '">';
            echo $subresult['title'];
            echo "</a>  ";
          }
        }
        echo '</td><td>';
        $words = explode(",", $row['words']);
        foreach ( $words as $word ) {
          echo $word .
            '(<a href="http://ja.wikipedia.org/wiki/' . $word . '">J</a>' .
            '<a href="http://en.wikipedia.org/wiki/' . $word . '">E</a>) ';
        }
        echo '</td></tr>';
      }
      echo '</table></div>';
    } catch (PDOException $e) {
      echo "DB error.";
      exit;
    }
?>
</body>
</html>
<?php
    exit;
  }
  // ### index.php with login or register ###
?>
<div id="top_menu">
  <form action="./index.php?menu=search">
  <p class="menu">
  <a href="./index.php?menu=index">Top</a> |
  <a href="./index.php?menu=profile">Profile</a> |
  <a href="./index.php?menu=tag">Tag</a> |
  <a href="./index.php?menu=imexport">(Im|Ex)port</a> |
  <a href="./index.php?menu=add">Add</a> |
  <a href="./index.php?menu=rss">RSS</a> |
  <a href="./index.php">Logout</a> | 
   Search: <input class="noboarder" type="text" />
   (<input type="checkbox" value="Tag"  name="tag" />Tag |
    <input type="checkbox" value="Flag" name="intro" />Intro ||
    <input type="checkbox" value="Mine" name="mine" />Mine )
   &nbsp; &nbsp;
  </p>
  </form>
</div>
<div id="contains">
<?php
  try {
    $stmt = $pdo->prepare("SELECT uid, tid, pass FROM " . DB_TBL_ACCOUNT .
                          " WHERE name = :name ;");
    $stmt->execute(array(':name' => $_SESSION['name']));
    $r = $stmt->fetch(PDO::FETCH_ASSOC);
    if($r) {
      if(!password_verify($_SESSION['pass'], $r['pass'])) {
        $r = false;
      }
    } else if($_REQUEST['register']) {
      // We're not in e-commerce, so no transaction here.
      $stmt = $pdo->prepare("INSERT INTO " . DB_TBL_TAG .
                            " (uid, title, intro, l_parent) VALUES " .
                            "(:uid, :title, :intro, :l_parent);");
      $stmt->execute(array(':uid'      => 0,
                           ':title'    => "user root",
                           ':intro'    => "user root",
                           ':l_parent' => 0));
      $tid = $pdo->lastInsertID('tid');
      $stmt = $pdo->prepare("INSERT INTO " . DB_TBL_ACCOUNT .
                            " (name, pass, email, tid) VALUES " .
                            "(:name, :pass, :email, :tid);");
      $stmt->execute(array(':name'  => $_SESSION['name'],
                           ':pass'  => password_hash($_SESSION['pass'], PASSWORD_BCRYPT),
                           ':email' => $_REQUEST['email'],
                           ':tid'   => $tid));
      $uid = $pdo->lastInsertID('uid');
      $stmt = $pdo->prepare("UPDATE " . DB_TBL_TAG .
                            " SET uid = :uid WHERE tid = :tid ;");
      $stmt->execute(array(':uid' => $uid, ':tid' => $tid));
      $stmt = $pdo->prepare("SELECT uid, tid FROM " . DB_TBL_ACCOUNT .
                            " WHERE name = :name ;");
      $stmt->execute(array(':name' => $_SESSION['name']));
      $r = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    if(!$r) {
      $_REQUEST['menu'] = 'logout';
    }
  } catch(PDOException $e) {
    echo "DB error";
    exit;
  }
  switch($_REQUEST['menu']) {
  case 'index':
  // ### index login index ###
?>
  <div align="center">
    <table>
<?php
    write_hierarchy(DB_TAG_0,  true, false);
    write_hierarchy($r['tid'], true, false);
    try {
      $stmt = $pdo->prepare("SELECT href, title, intro, tag, words, nid FROM ".
                            DB_TBL_NODE . " WHERE uid = :uid ;");
      $stmt->execute(array(':uid' => $r['uid']));
      $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
      foreach($rows as $row) {
        echo '<tr><td>';
        echo '<a href="' . $row['href'] . '">' . $row['title'] . '</a></td>';
        echo '<td>' . $row['intro'] . '</td>';
        $tags  = explode(",", $row['tag']);
        echo '<td>';
        foreach($tags as $tag) {
          $stmt2 = $pdo->prepare("SELECT title FROM " . DB_TBL_TAG . " WHERE " .
                                 "tid = :tid and uid = :uid ;");
          $stmt2->execute(array(':tid' => $tag, ':uid' => $r['uid']));
          if($subresult = $stmt2->fetch(PDO::FETCH_ASSOC)) {
            echo '<a href="index.php?menu=tag&tid=' . $tag . '">';
            echo $subresult['title'];
            echo "</a>  ";
          }
        }
        echo '</td><td>';
        $words = explode(",", $row['words']);
        foreach ( $words as $word ) {
          echo $word .
            '(<a href="http://ja.wikipedia.org/wiki/' . $word . '">J</a>' .
            '<a href="http://en.wikipedia.org/wiki/' . $word . '">E</a>) ';
        }
        echo '</td><td><a href="index.php?menu=delete&nid=' . $row['nid'] . '">Del</a></td>';
        echo "</tr>";
      }
      echo "</table></div></div>";
    } catch (PDOException $e) {
      echo "</table>DB error</div></div>";
      exit;
    }
    break;
  // ### index profile ###
  case 'profile':
?>
  <div align="center">
    <form action="index.php?menu=profile" method="post">
    <table class="noboarder">
    <tr class="noboarder"><td class="noboarder">ID: </td>
      <td class="noboarder"><?php echo $_SESSION['name']; ?></td></tr>
    <tr class="noboarder"><td class="noboarder">Pass: </td>
      <td class="noboarder"><input type="submit" name="change" value="Change Password"/></td></tr>
    <tr class="noboarder"><td class="noboarder">EMail: </td>
      <td class="noboarder"><input class="noboarder" type="text" name="email" />
        <input type="checkbox" name="p_email" /></td></tr>
    <tr class="noboarder"><td class="noboarder">Blog(or your site): </td>
      <td class="noboarder"><input class="noboarder" type="text" name="blog" /></td></tr>
    <tr class="noboarder"><td class="noboarder">Self Introduce: </td>
      <td class="noboarder"><textarea class="noboarder" name=""></textarea></td></tr>
    </table>
    <input type="submit" value="Change Profile" />
    </form>
  </div>
</div>
<?php
    break;
  // ### index add ###
  case 'add':
?>
  <div align="center">
    <p class="comment">
    Please ensure that your comment are global-readable by default.
    </p>
<?php
    if(isset($_REQUEST['url']) and $_REQUEST['url'] != "") {
      $tag  = trace_hierarchy(DB_TAG_0, $_REQUEST);
      $tag  = $tag . trace_hierarchy($r['tid'], $_REQUEST);
      try {
        $stmt = $pdo->prepare("INSERT INTO " . DB_TBL_NODE .
                              " (uid, title, href, intro, tag, words) VALUES " .
                              "(:uid, :title, :url, :comment, :tag, :words);");
        $stmt->execute(array(':uid'     => $r['uid'],
                             ':title'   => $_REQUEST['title'],
                             ':url'     => $_REQUEST['url'],
                             ':comment' => $_REQUEST['comment'],
                             ':tag'     => $tag,
                             ':words'   => $_REQUEST['words']));
        echo '<p class="comment">New bookmark to ' . $_REQUEST['url'] .
          ' had added.</p>';
      } catch (PDOException $e) {
        echo "DB error";
        exit;
      }
    }
?>
  <form action="index.php?menu=add" method="post">
  <table class="noboarder">
  <tr class="noboarder"><td class="noboarder">Title: </td>
    <td class="noboarder"><input class="noboarder"
      type="text" name="title" /></td></tr>
  <tr class="noboarder"><td class="noboarder">URL: </td>
    <td class="noboarder"><input class="noboarder"
      type="text" name="url" /></td></tr>
  <tr class="noboarder"><td class="noboarder">Comment: </td>
    <td class="noboarder">
      <textarea class="noboarder" name="comment"></textarea></td></tr>
  <tr class="noboarder"><td class="noboarder">Words("," separated): </td>
    <td class="noboarder"><input class="noboarder" type="text" name="words" />
     </td></tr>
  <tr class="noboarder"><td class="noboarder">Flags: </td><td>
<?php
    // attribute tag
    write_hierarchy(DB_TAG_0,  true, false);
    write_hierarchy($r['tid'], true, false);
?>
    </td></tr>
  </table>
  <input class="noboarder" type="submit" value="Get it added now." />
  </form>
  </div>
</div>
<?php
    break;
  // ### index delete ###
  case 'delete':
    echo '<div align="center">';
    if(isset($_REQUEST['nid']) and $_REQUEST['nid'] != "") {
      try {
        $stmt = $pdo->prepare("DELETE FROM " . DB_TBL_NODE .
                              " WHERE nid = :nid AND uid = :uid ;");
        $stmt->execute(array(':nid' => $_REQUEST['nid'],
                             ':uid' => $r['uid']));
        echo "Deleted.";
      } catch (PDOException $e) {
        echo "DB error";
      }
    }
    echo "</div></div>";
    break;
  // ### index tag ###
  case 'tag':
    if(isset($_REQUEST['mode'])) {
      switch($_REQUEST['mode']) {
      case "new":
        if(!isset($_REQUEST['title'])) {
          echo "Invalid request.";
        } else {
          $m = trace_hierarchy($r['tid'], array('chktid_' . $_REQUEST['tid'] => 'c'));
          if($_REQUEST['tid'] == $r['tid'] ||
             strpos($m, " " . $_REQUEST['tid'] . ",") !== false) {
            try {
              $stmt = $pdo->prepare("INSERT INTO " . DB_TBL_TAG .
                                    " (uid, title, intro, words, l_parent) " .
                                    " VALUES (:uid, :title, :intro, :words, :tid);");
              $stmt->execute(array(':uid'   => $r['uid'],
                                   ':title' => $_REQUEST['title'],
                                   ':intro' => $_REQUEST['intro'],
                                   ':words' => $_REQUEST['words'],
                                   ':tid'   => $_REQUEST['tid']));
            } catch (PDOException $e) {
              echo "DB error.";
              exit;
            }
          }
        }
        break;
      case "edit":
        break;
      case "delete":
        if($_REQUEST['tid'] == $tid) {
          echo "Cannot delete root node.";
          break;
        }
        try {
          $stmt = $pdo->prepare("SELECT tid FROM " . DB_TBL_TAG . " WHERE " .
                                "uid = :uid and tid = :tid ;");
          $stmt->execute(array(':uid' => $r['uid'],
                               ':tid' => $_REQUEST['tid']));
          if(! $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "No such tag exists.";
          } else {
            $stmt = $pdo->prepare("SELECT nid FROM " . DB_TBL_NODE .
                                  " WHERE tag LIKE :tag");
            $stmt->execute(array(':tag' => "%" . $_REQUEST['tid'] . "%"));
            if(! $stmt->fetch(PDO::FETCH_ASSOC)) {
              $stmt = $pdo->prepare("SELECT tid FROM " . DB_TBL_TAG .
                                    " WHERE l_parent = :tid ;");
              $stmt->execute(array(':tid' => $_REQUEST['tid']));
              if(! $stmt->fetch(PDO::FETCH_ASSOC)) {
                $stmt = $pdo->prepare("DELETE FROM " . DB_TBL_TAG . " WHERE " .
                                      "uid = :uid AND tid = :tid ;");
                $stmt->execute(array(':uid' => $r['uid'],
                                     ':tid'  => $_REQUEST['tid']));
              } else {
                echo "There exists siblings.";
              }
            } else {
              echo "There exists leaf nodes.";
            }
          }
        } catch(PDOException $e) {
          echo "DB error.";
          exit;
        }
        break;
      default:
        ;
      }
    }
    write_hierarchy($r['tid'], false, true);
    echo "</div>";
    break;
  // ### index im/export ###
  case 'imexport':
?>
  <div align="center">
  <form action="index.php?menu=imexport" method="post">
  <table class="noboarder">
  <tr class="noboarder"><td class="noboarder">Import: </td>
    <td class="noboarder">
      <input class="noboarder" id="import" type="file" /></td></tr>
  <tr class="noboarder"><td class="noboarder">Export: </td>
    <td class=noboarder>
      <input type="radio" id="firefox" name="type" />firefox |
      <input type="radio" id="sql"     name="type" />SQL |
      <input type="radio" id="graph"   name="type" />Graph</td></tr>
  </table>
  <input type="submit" /> <br/><br/>
  <p class="comment">Tags to be attributed or to be exported:</p>
<?php
    // attribute tag
    write_hierarchy(DB_TAG_0,  true, false);
    write_hierarchy($r['tid'], true, false);
    echo "</form></div></div>";
    break;
  // ### index logout ###
  default:
    session_destroy();
    echo 'Logged out, <a href="index.php?menu=index">please click here</a>.' .
         "</div>";
  }
  $pdo = NULL;
?>
</body>
</html>
