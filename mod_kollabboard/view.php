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
 * The main Whiteboard plugin view
 *
 * @package   mod_whiteboard
 * @copyright 2025 (Your Name/School)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once(dirname(__FILE__).'/lib.php');

$id = optional_param('id', 0, PARAM_INT);

if ($id) {
    $cm = get_coursemodule_from_id('whiteboard', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $whiteboard = $DB->get_record('whiteboard', array('id' => $cm->instance), '*', MUST_EXIST);
} else {
    error('You must specify a course_module ID or an instance ID');
}

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/whiteboard:view', $context);

// Trigger module viewed event.
$event = \mod_whiteboard\event\course_module_viewed::create(array(
    'objectid' => $whiteboard->id,
    'context' => $context,
));
$event->add_record_snapshot('course_modules', $cm);
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('whiteboard', $whiteboard);
$event->trigger();

$PAGE->set_url('/mod/kollabboard/view.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($kollabboard->name));
$PAGE->set_heading($course->fullname);
$PAGE->requires->css('/mod/kollabboard/style.css');

// Set page title and heading.
$PAGE->set_title($whiteboard->name);
$PAGE->set_heading(format_string($whiteboard->name));
$PAGE->set_url('/mod/whiteboard/view.php', array('id' => $cm->id));

// Get the configured whiteboard server URL. Default to the local Excalidraw server.
$excalidraw_server = trim(get_config('mod_whiteboard', 'excalidraw_server'));
if (empty($excalidraw_server)) {
    // For local development: use localhost:5000 which is accessible from the browser
    // For Docker internal communication, this will be proxied through the host
    $excalidraw_server = 'http://localhost:5000';
}

// Build the whiteboard URL for a collaborative room session.
// Note: Excalidraw expects the room parameter as a query parameter (?room=) for proper WebSocket collaboration.
// The encryption_key is NOT used by Excalidraw for room identification - only room_id is required.
$whiteboard_url = rtrim($excalidraw_server, '/') . '/?room=' . $whiteboard->room_id;

// Output the page header.
echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($kollabboard->name));

<<<<<<< HEAD
// Display the iframe embedding the Excalidraw whiteboard.
// IMPORTANT: For real-time collaboration to work, the following must be configured:
// 1. excalidraw-room service must be running on port 80 (WebSocket standard port, mapped to host port 3002)
// 2. excalidraw frontend must have WebSocket URL replaced from oss-collab.excalidraw.com to http://excalidraw-room:80
// 3. Browser must be able to access http://localhost:5000 (Excalidraw frontend)
// 4. The room_id must be identical for all users accessing the same whiteboard instance
=======
if (!empty($kollabboard->intro)) {
    echo $OUTPUT->box(format_module_intro('kollabboard', $kollabboard, $cm->id), 'generalbox', 'intro');
}

$boardurl = get_config('mod_kollabboard', 'boardurl');

if (empty($boardurl)) {
    echo $OUTPUT->notification(get_string('boardurl_missing', 'mod_kollabboard'), 'error');
    echo $OUTPUT->footer();
    exit;
}

// Aktive Gruppe der Aktivität ermitteln (0 = keine Gruppen / alle Teilnehmer).
$groupid = groups_get_activity_group($cm) ?: 0;

$room = kollabboard_get_room($cm->id, $groupid);

// Raum registrieren, damit der (unauthentifizierte) Storage-Endpoint ihn bedient.
kollabboard_register_room($room['roomid'], $cm->instance, $groupid);

// Anzeigename der/des Nutzenden aus Moodle, damit auf dem Board echte Namen
// statt Excalidraw-Zufallsnamen erscheinen (per URL-Query an den Fork übergeben).
$displayname = fullname($USER);

// Der Excalidraw-Editor liegt unter /app; Name per Query, Raum per URL-Fragment.
$boardsrc = rtrim($boardurl, '/') . '/app?username=' . rawurlencode($displayname)
    . '#room=' . $room['roomid'] . ',' . $room['roomkey'];

echo html_writer::start_div('kollabboard-frame-wrap');
echo html_writer::tag('iframe', '', [
    'src' => $boardsrc,
    'title' => format_string($kollabboard->name),
    'class' => 'kollabboard-frame',
    'allow' => 'clipboard-read; clipboard-write; fullscreen',
    'allowfullscreen' => 'allowfullscreen',
]);
echo html_writer::end_div();
>>>>>>> c0f6d53ac5b71197785f2f5f35c75755acc3bbbc

echo $OUTPUT->heading(get_string('whiteboard', 'mod_whiteboard'));
echo html_writer::start_tag('div', array('style' => 'width: 100%; height: 80vh; border: 1px solid #ddd;'));
echo html_writer::tag('iframe', '', array(
    'src' => $whiteboard_url,
    'style' => 'width: 100%; height: 100%; border: none;',
    'allow' => 'fullscreen',
    'allowfullscreen' => 'true'
));
echo html_writer::end_tag('div');

// Optional: Add direct link for users who prefer to open in new tab
echo html_writer::start_tag('div', array('style' => 'margin-top: 10px;'));
echo html_writer::link($whiteboard_url, get_string('open_in_new_tab', 'mod_whiteboard'), array('target' => '_blank'));
echo html_writer::end_tag('div');

// Output the page footer.
echo $OUTPUT->footer();
