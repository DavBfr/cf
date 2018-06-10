/**
 * Copyright (C) 2013-2018 David PHAM-VAN
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

function restError(response) {
	if (cf_options.debug) {
		if (response.status === 500) {
			let div = document.createElement('div');
			let iFrame = document.createElement('iframe');
			let btn = document.createElement('div');
			btn.innerHTML = "X";
			btn.style = "position:absolute;border-radius:20px;font-size:12px;height:40px;line-height:1.42;padding:11px 0;text-align: center;width:40px;background-color:#768f62;color:white;z-index:100001;cursor:pointer;right:30px;";
			btn.addEventListener("click", function () {
				document.body.removeChild(div);
			});
			div.style = "background-color:white;position:absolute;left:10px;top:10px;bottom:10px;right:10px;border:1px solid red;z-index:100000;padding:10px;";
			iFrame.style = "position:absolute;left:0;top:0;width:100%;height:100%;border:none;";
			div.appendChild(btn);
			div.appendChild(iFrame);
			document.body.appendChild(div);
			iFrame.contentWindow.document.open();
			iFrame.contentWindow.document.write(response.data);
			iFrame.contentWindow.document.close();
			return true;
		}
	}

	if (response.status === 401 && cf_options.user !== false) {// Unauthorized
		let injector = angular.element(document).injector();
		let LoginService = injector.get('LoginService');
		LoginService.check((next) => {
			if (next === false) {
				window.location.reload();
			}
		});
	}

	return false;
}
