;/**
 * tools.js
 * http://enviragallery.com/
 * ==========================================================
 * Copyright 2018 Envira Gallery Team
 *
 * Licensed under the GPL License, Version 2.0 or later (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 * ========================================================== */ (function ($) {
	$(
		function () {
			$( 'body.post-type-envira' ).on(
				'click',
				'a.editinline',
				function (event) {
					event.stopPropagation;
					$( this ).toggleClass( 'envira-log-show-details' );
					if ($( this ).hasClass( 'envira-log-show-details' )) {
						var element_to_copy = $( this ).closest( 'td' ).find( 'div.test123' ).html();
						var temp_row        = '<tr><th scope="row" class="check-column"></th><td colspan="5">' + element_to_copy + '</td></tr>';
						$( this ).closest( 'tr' ).after( temp_row );
					} else {
						$( this ).closest( 'tr' ).next().remove();
					}
				}
			);

			$( '.envira-log-filter-cat' ).live(
				'change',
				function () {
					var catFilter = $( this ).val();
					if (catFilter != '') {
						document.location.href = 'edit.php?post_type=envira&page=envira-gallery-tools' + catFilter + '#!envira-tab-logs';
					} else {
						document.location.href = 'edit.php?post_type=envira&page=envira-gallery-tools#!envira-tab-logs';
					}
				}
			);
		}
	);
}(jQuery));
