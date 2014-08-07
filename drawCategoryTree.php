<?php
define('APPLICATION_ENV', 'latvia');
$application = require_once(dirname(__FILE__) . '/../common/' . '/cli-loader.php');
$application->getBootstrap()->getService('sabio.acl')->useDefaultCliUserLogin();
//restore_error_handler();
// Time limit for script is controlled by process controller client
set_time_limit(0);
ini_set('display_errors', E_ALL);

function drawChilds($rootId, $leftIndex, $rightIndex, &$parent) {
    global $em;
    for ($left = $leftIndex + 1; $left < $rightIndex;) {
        $children = $em->getConnection()->executeQuery(
            'SELECT * FROM category WHERE root_id = ? AND left_index = ?', array($rootId, $left)
        )->fetch(PDO::FETCH_ASSOC);
        $childs = array();
        if ($children === false) {
            $parent[99999 + $left] = array(
                'data' =>
                    array('name' => 'NOT EXISTS', 'left_index' => $left, 'right_index' => 0, 'norder' => -1),
                'childs' => $childs
            );
            $left++;
            continue;
        }
        $left = drawChilds($rootId, $children['left_index'], $children['right_index'], $childs);
        $parent[$children['category_id']] = array('data' => $children, 'childs' => $childs);
    }
    return $rightIndex + 1;
}
/** @var \Doctrine\ORM\EntityManager $em */
$em = $application->getBootstrap()->getService('entitymanager');

if (isset($_GET['store_id'])) {
    $storeId = $_GET['store_id'];
} else {
    $storeId = 7;
}
$root = $em->getConnection()->executeQuery(
    'SELECT c.root_id, c.right_index, c.left_index, c.category_id, c.name, c.norder
    FROM store s
    INNER JOIN category c ON c.root_id = s.category_id AND c.parent_id IS NULL
    WHERE s.store_id = ?', array($storeId)
)->fetch(PDO::FETCH_ASSOC);

$categories = array();
drawChilds($root['root_id'], $root['left_index'], $root['right_index'], $categories);

$data[$root['category_id']] = array('data' => $root, 'childs' => $categories);

include 'category_tree.phtml';