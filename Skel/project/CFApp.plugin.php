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

class CFAppPlugin extends Plugins {

	public function cli($cli) {
		$cli->addCommand("run", array($this, "run"), "Start this application");
	}

	public function run() {
		system("php -S 127.0.0.1:3000 -t " . WWW_DIR);
	}

	public function install() {
	}

	public function update() {
	}

}
