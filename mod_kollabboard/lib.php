<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Library functions for Whiteboard plugin
 *
 * @package   mod_whiteboard
 * @copyright 2025 (Your Name/School)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Add whiteboard instance
 *
 * @param object $data Data from the form
 * @return int The whiteboard instance ID
 */
function whiteboard_add_instance($data) {
    global $DB;

    $data->timecreated = time();
    $data->timemodified = time();
    $data->room_id = whiteboard_generate_room_id();
    $data->encryption_key = whiteboard_generate_encryption_key();

    $id = $DB->insert_record('whiteboard', $data);

    return $id;
}

/**
 * Update whiteboard instance
 *
 * @param object $data Data from the form
 * @return bool
 */
function whiteboard_update_instance($data) {
    global $DB;

    $data->id = $data->instance;
    $data->timemodified = time();

    return $DB->update_record('whiteboard', $data);
}

/**
 * Delete whiteboard instance
 *
 * @param int $id The whiteboard instance ID
 * @return bool
 */
function whiteboard_delete_instance($id) {
    global $DB;
    return $DB->delete_records('whiteboard', array('id' => $id));
}

/**
 * Generate a unique room ID for Excalidraw
 *
 * @return string A unique 12-character room ID (compatible with Excalidraw)
 */
function whiteboard_generate_room_id() {
    // Generate exactly 12 lowercase alphanumeric characters for the collaboration room ID.
    // Note: Excalidraw typically expects room IDs to be 12 characters long for optimal compatibility.
    $chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
    $roomid = '';
    for ($i = 0; $i < 12; $i++) {
        $roomid .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $roomid;
}

/**
 * Generate a 22-character encryption key
 *
 * @return string A 22-character encryption key
 */
function whiteboard_generate_encryption_key() {
    // Generate a 22-character key containing uppercase, lowercase, digits and hyphen.
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-';
    $key = '';
    for ($i = 0; $i < 22; $i++) {
        $key .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $key;
}

/**
 * Supports the following features:
 *  - FEATURE_GRADE_HAS_GRADE
 *  - FEATURE_GRADE_OUTCOMES
 *  - FEATURE_GROUPS
 *  - FEATURE_GROUPINGS
 *  - FEATURE_MOD_INTRO
 *  - FEATURE_COMPLETION_TRACKS_VIEWS
 *  - FEATURE_BACKUP_MOODLE2
 *
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, false otherwise, null if doesn't know
 */
function whiteboard_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        default:
            return null;
    }
}

/**
 * Return the list of view actions
 *
 * @return array
 */
function whiteboard_get_view_actions() {
    return array('view', 'view all');
}

/**
 * Return the list of post actions
 *
 * @return array
 */
function whiteboard_get_post_actions() {
    return array('update', 'add');
}
 * Leitet Raum-ID und Ende-zu-Ende-Schlüssel für ein Board deterministisch ab.
 *
 * Gleiche (cmid, groupid) ergeben denselben Raum für alle Teilnehmer, sind aber
 * ohne das serverseitige Geheimnis nicht erratbar. Der Schlüssel wird nur an
 * berechtigte, eingeloggte Nutzer ausgeliefert (siehe view.php).
 *
 * @param int $cmid Course-Module-ID des Boards
 * @param int $groupid Gruppen-ID (0 = gemeinsames Board)
 * @return array{roomid: string, roomkey: string}
 */
function kollabboard_get_room($cmid, $groupid) {
    $secret = get_config('mod_kollabboard', 'roomsecret');
    if (empty($secret)) {
        $secret = bin2hex(random_bytes(32));
        set_config('roomsecret', $secret, 'mod_kollabboard');
    }

    $roomid = substr(bin2hex(hash_hmac('sha256', "id:$cmid:$groupid", $secret, true)), 0, 20);

    // 128-Bit-Schlüssel als base64url ohne Padding (22 Zeichen) – Format, das Excalidraw erwartet.
    $keybytes = substr(hash_hmac('sha256', "key:$cmid:$groupid", $secret, true), 0, 16);
    $roomkey = rtrim(strtr(base64_encode($keybytes), '+/', '-_'), '=');

    return ['roomid' => $roomid, 'roomkey' => $roomkey];
}

/**
 * Registriert einen Raum in der Datenbank, falls noch nicht vorhanden.
 *
 * Nur registrierte Räume werden vom Storage-Endpoint (storage.php) bedient. Die
 * Registrierung erfolgt ausschließlich aus view.php heraus, d.h. durch einen
 * eingeloggten, berechtigten Nutzer – der unauthentifizierte Storage-Endpoint kann
 * so keine beliebigen Räume anlegen.
 *
 * @param string $roomid Abgeleitete Raum-ID
 * @param int $kollabboardid Instanz-ID des Boards (kollabboard.id)
 * @param int $groupid Gruppen-ID (0 = gemeinsames Board)
 * @return void
 */
function kollabboard_register_room($roomid, $kollabboardid, $groupid) {
    global $DB;
    if ($DB->record_exists('kollabboard_boards', ['roomid' => $roomid])) {
        return;
    }
    $now = time();
    $DB->insert_record('kollabboard_boards', (object) [
        'roomid'        => $roomid,
        'kollabboardid' => $kollabboardid,
        'groupid'       => $groupid,
        'sceneversion'  => 0,
        'sceneblob'     => null,
        'savedby'       => 0,
        'timecreated'   => $now,
        'timemodified'  => $now,
    ]);
}
