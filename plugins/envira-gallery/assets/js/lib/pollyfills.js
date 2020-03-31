/*!
 * Envira Pollyfills
 */

/* add compatible Object.entries support for IE 11 */

if ( ! Object.entries) {
	Object.entries = function( obj ){
		var ownProps = Object.keys( obj ),
		i            = ownProps.length,
		resArray     = new Array( i ); // preallocate the Array
		while (i--) {
			resArray[i] = [ownProps[i], obj[ownProps[i]]];
		}

		return resArray;
	};
}
