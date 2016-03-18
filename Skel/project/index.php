<?php namespace DavBfr\CF;
/**
 * Copyright (c) 2016 @CF_AUTHOR@
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
 * INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A
 * PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
 * OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE
 * SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 * Generated with CF @CF_VERSION@ on @DATE@
 **/

if (!file_exists(dirname(__file__) . "/config/paths.php"))
	die("Site not configured.");
include_once(dirname(__file__) . "/config/paths.php");

if (file_exists(dirname(__file__) . "/vendor/autoload.php"))
	include_once(dirname(__file__) . "/vendor/autoload.php");
elseif (!file_exists(CF_DIR . "/cf.php"))
	die("Site not configured correctly.");
else
	require_once(CF_DIR . "/cf.php");

$tpt = CorePlugin::bootstrap();
configure("CF_TEMPLATE", "index.php");
$tpt->outputCached(CF_TEMPLATE);
