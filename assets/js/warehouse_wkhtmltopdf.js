/**
 * Wkhtmltopdf warehouse
 * Functions & onload call
 *
 * Called when generating HTML for PDF
 *
 * @see   functions/PDF.php
 *
 * @since 2.9
 */

window.onload = function() {
	MarkDownToHTML();
};

/**
 * MarkDown text to HTML
 *
 * @since 6.0 JS MarkDown use marked instead of showdown (15KB smaller).
 *
 * Parses Text inside
 * <div class="markdown-to-html">_MD text_</div>
 *
 * @uses marked
 */
function MarkDownToHTML() {
	// Open links in new window.
	// @link https://github.com/markedjs/marked/issues/144
	var renderer = new marked.Renderer();

	renderer.link = function(href, title, text) {
	    var link = marked.Renderer.prototype.link.call(this, href, title, text);
	    return link.replace("<a","<a target='_blank' ");
	};

	// Set options.
	// @link https://marked.js.org/#/USING_ADVANCED.md
	marked.setOptions({
		breaks: true, // Add <br> on a single line break. Requires gfm be true.
		gfm: true, // GitHub Flavored Markdown (GFM).
		headerIds: false, // Include an id attribute when emitting headings (h1, h2, h3, etc).
		renderer: renderer,
	});

	var els = document.getElementsByClassName('markdown-to-html'),
		i;

	for (i in els) {
		if ( els[i].innerHTML ) {
			// Note: DOMPurify is not used here. Does not load (PDF only).
			els[i].innerHTML = marked.parse(els[i].innerHTML);
		}
	}
}
