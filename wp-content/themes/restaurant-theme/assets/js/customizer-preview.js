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

    wp.customize( 'header_bg_color', function ( value ) {
        value.bind( function ( newVal ) {
            document.documentElement.style.setProperty( '--header-bg-color', newVal );
        } );
    } );

    wp.customize( 'footer_bg_color', function ( value ) {
        value.bind( function ( newVal ) {
            document.documentElement.style.setProperty( '--footer-bg-color', newVal );
        } );
    } );

    wp.customize( 'footer_font_size', function ( value ) {
        value.bind( function ( newVal ) {
            document.documentElement.style.setProperty( '--footer-font-size', parseInt( newVal, 10 ) + 'px' );
        } );
    } );

    wp.customize( 'parallax_height', function ( value ) {
        value.bind( function ( newVal ) {
            document.documentElement.style.setProperty( '--parallax-height', parseInt( newVal, 10 ) + 'px' );
        } );
    } );

    wp.customize( 'footer_logo_height', function ( value ) {
        value.bind( function ( newVal ) {
            document.documentElement.style.setProperty( '--footer-logo-height', parseInt( newVal, 10 ) + 'px' );
        } );
    } );
} )();
