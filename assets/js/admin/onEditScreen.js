/*
JS for Edit Screen.

@since 1.0.0
@version 1.0.0
*/

/**
 * @link https://developer.wordpress.org/block-editor/data/data-core-editor/#lockPostSaving
 */
const {
	subscribe,
	select,
	dispatch,
} = wp.data;

/**
 * "Status and visibility' panel needs to be open to display "Move To Trash" button (rest of panel content is hidden using css).
 */
function openPostStatus() {

	/**
	 * Getting deprecated notice when using this - had to trawl through WP core to find out what to replace it with, finally found
	 * this file: https://github.com/WordPress/gutenberg/blob/trunk/packages/edit-post/src/index.native.js that pointed me in
	 * a direction where I was able to figure out what to replace it with.
	 */
	// var preferences = wp.data.select('core/edit-post').getPreferences();
	var postStatusOpen = wp.data.select( 'core/preferences' ).get('core/edit-post', 'openPanels').includes('post-status');

	// console.log(wp.data.select( 'core/preferences' ).get('core/edit-post', 'openPanels'));
	// console.log(wp.data.select( 'core/preferences' ).get('core/edit-post', 'openPanels').includes('post-status'));

	// if ( ! preferences['panels']['post-status'] ) {
	if ( ! postStatusOpen ) {

		// Open the post-status panel.
		wp.data.dispatch( 'core/edit-post' ).toggleEditorPanelOpened( 'post-status' );

	}

}

/**
 * Add left/right padding on mobile if not using theme styles because there isn't any padding so content sits right up
 * against edge of viewport which looks naff.
 */
function mobPadding() {

	var themeStyles = wp.data.select( 'core/preferences' ).get('core/edit-post', 'themeStyles');

	if ( ! themeStyles ) {

		document.body.classList.add("ept-no-theme-styles");

	}

}

// Using domReady which is called when Gutenberg is ready (I think)
wp.domReady( function() {

	openPostStatus();
	mobPadding();

} );