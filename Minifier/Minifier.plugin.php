<?php namespace DavBfr\CF;
/**
 * Copyright (C) 2013-2015 David PHAM-VAN
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 **/

Options::set("MINIFY_JSCSS", !DEBUG, "Minify Javascript and CSS files");
Options::set("MINIFY_HTML", !DEBUG, "Minify Html files");
Options::set("MINIFY_YUI", false, "Use yui-compressor to minify");

class MinifierPlugin extends Plugins {

	private static function globRecursive($pattern, $flags = 0) {
		$files = glob($pattern, $flags);

		foreach (glob(dirname($pattern) . '/*', GLOB_ONLYDIR | GLOB_NOSORT) as $dir){
			$files = array_merge($files, self::globRecursive($dir . '/' . basename($pattern), $flags));
		}

		return $files;
	}


	public static function minify_images() {
		$path = Cli::addOption("path", WWW_PATH, "Path where to find images");
		$norun = Cli::addSwitch("n", "Do not run the scripts, only print files to process");
		Cli::enableHelp();

		Cli::pinfo("Minify images");
		foreach (self::globRecursive($path . "/{*.[pP][nN][gG],*.[gG][iI][fF]}", GLOB_BRACE | GLOB_NOSORT) as $png) {
			Cli::pinfo(" * $png");
			$output = "";
			$return_var = -1;
			$cmd = "optipng -o7 -strip all $png";
			if ($norun) {
				Logger::Debug("   > $cmd");
			} else {
				exec("$cmd 2>& 1", $output, $return_var);
				if ($return_var != 0) {
					Cli::perr(implode("\n", $output));
				} else {
					Logger::Debug(implode("\n", $output));
				}
			}
		}
		foreach (self::globRecursive($path . "/{*.[jJ][pP][gG],*.[jJ][pP][eE][gG]}", GLOB_BRACE | GLOB_NOSORT) as $jpg) {
			Cli::pinfo(" * $jpg");
			$output = "";
			$return_var = -1;
			$cmd = "jpegoptim -s -v -v $jpg";
			if ($norun) {
				Logger::Debug("   > $cmd");
			} else {
				exec("$cmd 2>& 1", $output, $return_var);
				if ($return_var != 0) {
					Cli::perr(implode("\n", $output));
				} else {
					Logger::Debug(implode("\n", $output));
				}
			}
		}
	}


	public function minify_html($input) {
		if (MINIFY_HTML)
			return HtmlMinifier::html($input);
		else
			return NULL;
	}


	public function cli($cli) {
		$cli->addCommand("minify:images", array(__NAMESPACE__ . "\\MinifierPlugin", "minify_images"), "Crush and Optimize Images");
	}

}
