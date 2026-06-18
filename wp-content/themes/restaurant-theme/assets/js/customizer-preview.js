/* Prévisualisation temps réel dans le Customizer (transport: postMessage) */
( function () {
    wp.customize( 'header_logo_height', function ( value ) {
        value.bind( function ( newVal ) {
            var h   = parseInt( newVal, 10 ) || 72;
            var hSm = Math.max( 30, Math.round( h * 0.75 ) );
            document.documentElement.style.setProperty( '--header-logo-height', h + 'px' );
            document.documentElement.style.setProperty( '--header-logo-height-scrolled', hSm + 'px' );
        } );
    } );
} )();
