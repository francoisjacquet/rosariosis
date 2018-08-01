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
 * Parses Text inside
 * <div class="markdown-to-html">_MD text_</div>
 *
 * @uses showdown.js
 */
function MarkDownToHTML() {
	var sdc = new showdown.Converter({
		tables: true,
		simplifiedAutoLink: true,
		parseImgDimensions: true,
		tasklists: true,
		literalMidWordUnderscores: true,
	});

	var els = document.getElementsByClassName('markdown-to-html'),
		i;

	for (i in els) {
		els[i].innerHTML = sdc.makeHtml(els[i].innerHTML);
	}
}
