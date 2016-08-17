<?php

/*
Plugin Name: Amazon Link Extra - Stylesheet
Plugin URI: http://www.houseindorset.co.uk/
Description: Update the Amazon Link plugin to use a new stylsheet, default location 'wp-content/plugins/user-styles.css'
Version: 1.0
Author: Paul Stuttard
Author URI: http://www.houseindorset.co.uk
*/

/*
Copyright 2013-2014 Paul Stuttard

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

*/

   add_filter( 'amazon_link_style_sheet', 'alx_styles_user_stylesheet' );
   
   function alx_styles_user_stylesheet ( $style_sheet )
   {
      /* Change 'user_styles.css' to be the path of the stylesheet relative to this file */
      return plugins_url( 'user_styles.css', __FILE__ );
   }
?>
