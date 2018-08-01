/**
 * DHTML tip message
 *
 * @version 1.7
 * @copyright Essam Gamal 2003
 * @copyright François Jacquet 2015-2018
 * @example <div onmouseover="stm([tiptitle,tipmsg]);">Tip</a>
 */

var mig = {};

function stm(t) {
	if (this.onmousemove != mig_mo) {
		this.onmousemove = mig_mo;
	}

	var title = t[0] ? "<THEAD><TR><TH>" + t[0] + "</TH></TR></THEAD>" : "",
		txt = "<TABLE class='widefat'>" + title + "<TR><TD>" + t[1] + "</TD></TR></TABLE>";

	mig.lay.innerHTML = txt;

	mig.count = 0;
	mig.move = 1;

	// Close Tip message.
	this.onmouseout = htm;
	mig.lay.click = htm;

	return false;
}

function mig_mo(e) {
	if (!mig.move) {
		return;
	}

	var X = 0,
		Y = 0,
		s_d = [parseInt(window.pageXOffset), parseInt(window.pageYOffset)],
		w_d = [parseInt(document.body.clientWidth), parseInt(document.body.clientHeight)],
		mx = e.pageX,
		my = e.pageY,
		e_d = [parseInt(mig.lay.offsetWidth) + 3, parseInt(mig.lay.offsetHeight) + 5],
		// Offset.
		tb = {
			xpos: 10,
			ypos: 10
		},
		sbw = 0;

	X = mx + tb.xpos;
	Y = my + tb.ypos;

	if (w_d[0] + s_d[0] < e_d[0] + X + sbw) X = w_d[0] + s_d[0] - e_d[0] - sbw;

	if (w_d[1] + s_d[1] < e_d[1] + Y + sbw) {
		Y = my - e_d[1];
	}

	if (X < s_d[0]) X = s_d[0];

	with(mig.lay.style) {
		left = X + 'px';
		top = Y + 'px';
	}

	mig.count++;

	if (mig.count == 1) mig.lay.style.visibility = "visible";
}

function htm() {
	mig.move = 0;

	mig.lay.innerHTML = '';

	with(mig.lay.style) {
		visibility = "hidden";
		left = 0;
		top = -800 + 'px';
	}

	// Unset.
	this.onmouseout = mig.lay.click = this.onmousemove = null;

	return false;
}

function mig_init() {
	var el = document.createElement('div');

	el.id = 'tipMsg';

	document.body.appendChild(el);

	mig.lay = el;
}

// Init.
$(document).ready(mig_init);
