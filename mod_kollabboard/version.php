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
 * Whiteboard module version info
 *
 * @package   mod_whiteboard
 * @copyright 2025 (Your Name/School)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->version   = 2026071000;
$plugin->requires  = 2022112800; // Moodle 4.0+
$plugin->maturity  = MATURITY_BETA;
$plugin->release   = '0.2.0 (Build: 20260710-0)';
$plugin->component = 'mod_whiteboard';
$plugin->cron      = 0;

$plugin->dependencies = array();
$plugin->component = 'mod_kollabboard';
$plugin->version   = 2026072902;
$plugin->requires  = 2025100600; // Moodle 5.1.0
$plugin->maturity  = MATURITY_ALPHA;
$plugin->release   = '0.1';
