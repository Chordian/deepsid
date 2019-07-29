<?php for($voice = 0; $voice <= 2; $voice++) : ?>
	<div class="piano-top-panel ptp<?php echo $voice; ?>">
		<div class="pv-wrap"><button class="piano-voice pv<?php echo $voice; // Don't move these classes around ?> voice-on"><?php echo $voice + 1; ?></button></div>
		<div class="bar-num" style="left:79px;">0</div>
		<div class="bar-num" style="left:173px;">800</div>
		<div class="bar-num" style="left:273px;">FFF</div>
		<div class="bar-num" style="left:116px;">Pulse</div>
		<div class="edges edges-pulse"></div>
		<div class="flat-triangle"></div>
		<canvas class="bar" id="piano-pw<?php echo $voice; ?>" width="200" height="8"></canvas>
		<div class="pm-carpet-left pcl-ringmod"></div><div class="pm-carpet-right pcr-ringmod"></div>
		<div class="piano-ringmod piano-rm<?php echo $voice; ?> piano-ringmod-left pr-off"></div><div class="piano-ringmod piano-rm<?php echo $voice; ?> piano-ringmod-right pr-off"></div>
		<div class="bar-num label-mod" style="left:375px;">RM</div>
		<div class="pm-carpet-left pcl-hardsync"></div><div class="pm-carpet-right pcr-hardsync"></div>
		<div class="piano-hardsync piano-hs<?php echo $voice; ?> piano-hardsync-left ph-off"></div><div class="piano-hardsync piano-hs<?php echo $voice; ?> piano-hardsync-right ph-off"></div>
		<div class="bar-num label-mod" style="left:412px;">HS</div>
		<div class="piano-filter<?php echo $voice; ?> bar-num" style="right:311px;">0</div>
		<div class="piano-filter<?php echo $voice; ?> bar-num" style="right:107px;">7FF</div>
		<div class="piano-filter<?php echo $voice; ?> bar-num" style="right:186px;">Filter cutoff</div>
		<div class="piano-filter<?php echo $voice; ?> edges edges-fc"></div>
		<canvas class="piano-filter<?php echo $voice; ?> bar" id="piano-fc<?php echo $voice; ?>" width="200" height="8"></canvas>
		<div class="piano-filter<?php echo $voice; ?> bar-num num-res" style="top:10px;">F</div>
		<div class="piano-filter<?php echo $voice; ?> bar-num num-res" style="top:24px;right:81px;">Res</div>
		<div class="piano-filter<?php echo $voice; ?> bar-num num-res" style="top:39px;">0</div>
		<div class="piano-filter<?php echo $voice; ?> edges-res"></div>
		<canvas class="piano-filter<?php echo $voice; ?> bar" id="piano-res<?php echo $voice; ?>" width="3" height="30"></canvas>
		<div class="piano-filter<?php echo $voice; ?> pb-wrap pb-lp pb-lp<?php echo $voice; ?>"><span>Low</span><div class="piano-pb-led piano-pb-led<?php echo $voice; ?> pb-off"></div></div>
		<div class="piano-filter<?php echo $voice; ?> pb-wrap pb-bp pb-bp<?php echo $voice; ?>"><span>Band</span><div class="piano-pb-led piano-pb-led<?php echo $voice; ?> pb-off"></div></div>
		<div class="piano-filter<?php echo $voice; ?> pb-wrap pb-hp pb-hp<?php echo $voice; ?>"><span>High</span><div class="piano-pb-led piano-pb-led<?php echo $voice; ?> pb-off"></div></div>
	</div>
	<div class="piano piano<?php echo $voice; ?>">
	<?php for($octave = 0; $octave <= 7; $octave++) : ?>
		<svg width="128" height="128" viewbox="0 0 77.5 76.9" style="position:absolute;top:0;left:<?php echo $octave * 104; ?>px;" xmlns="http://www.w3.org/2000/svg" xmlns:svg="http://www.w3.org/2000/svg">
			<path class="white" id="v<?php echo $voice; ?>_oct<?php echo $octave; ?>_11" d="m55.75595,49.71429c0,1.104 0.896,2 2,2l5,0c1.104,0 2,-0.896 2,-2l0,-46c0,-1.104 -0.896,-2 -2,-2l-5,0c-1.104,0 -2,0.896 -2,2l0,46z" fill="#fff"/>
			<path d="m55.75595,49.71429c0,1.104 0.896,2 2,2l5,0c1.104,0 2,-0.896 2,-2l0,-46c0,-1.104 -0.896,-2 -2,-2l-5,0c-1.104,0 -2,0.896 -2,2l0,46z" fill="none" stroke="#000"/>
			<path class="white" id="v<?php echo $voice; ?>_oct<?php echo $octave; ?>_9" d="m46.75595,49.71429c0,1.104 0.896,2 2,2l5,0c1.104,0 2,-0.896 2,-2l0,-46c0,-1.104 -0.896,-2 -2,-2l-5,0c-1.104,0 -2,0.896 -2,2l0,46z" fill="#fff"/>
			<path d="m46.75595,49.71429c0,1.104 0.896,2 2,2l5,0c1.104,0 2,-0.896 2,-2l0,-46c0,-1.104 -0.896,-2 -2,-2l-5,0c-1.104,0 -2,0.896 -2,2l0,46z" fill="none" stroke="#000"/>
			<path class="white" id="v<?php echo $voice; ?>_oct<?php echo $octave; ?>_7" d="m37.75595,49.71429c0,1.104 0.896,2 2,2l5,0c1.104,0 2,-0.896 2,-2l0,-46c0,-1.104 -0.896,-2 -2,-2l-5,0c-1.104,0 -2,0.896 -2,2l0,46z" fill="#fff"/>
			<path d="m37.75595,49.71429c0,1.104 0.896,2 2,2l5,0c1.104,0 2,-0.896 2,-2l0,-46c0,-1.104 -0.896,-2 -2,-2l-5,0c-1.104,0 -2,0.896 -2,2l0,46z" fill="none" stroke="#000"/>
			<path class="white" id="v<?php echo $voice; ?>_oct<?php echo $octave; ?>_5" d="m28.75595,49.71429c0,1.104 0.896,2 2,2l5,0c1.104,0 2,-0.896 2,-2l0,-46c0,-1.104 -0.896,-2 -2,-2l-5,0c-1.104,0 -2,0.896 -2,2l0,46z" fill="#fff"/>
			<path d="m28.75595,49.71429c0,1.104 0.896,2 2,2l5,0c1.104,0 2,-0.896 2,-2l0,-46c0,-1.104 -0.896,-2 -2,-2l-5,0c-1.104,0 -2,0.896 -2,2l0,46z" fill="none" stroke="#000"/>
			<path class="white" id="v<?php echo $voice; ?>_oct<?php echo $octave; ?>_4" d="m19.75595,49.71429c0,1.104 0.896,2 2,2l5,0c1.104,0 2,-0.896 2,-2l0,-46c0,-1.104 -0.896,-2 -2,-2l-5,0c-1.104,0 -2,0.896 -2,2l0,46z" fill="#fff"/>
			<path d="m19.75595,49.71429c0,1.104 0.896,2 2,2l5,0c1.104,0 2,-0.896 2,-2l0,-46c0,-1.104 -0.896,-2 -2,-2l-5,0c-1.104,0 -2,0.896 -2,2l0,46z" fill="none" stroke="#000"/>
			<path class="white" id="v<?php echo $voice; ?>_oct<?php echo $octave; ?>_2" d="m10.75595,49.71429c0,1.104 0.896,2 2,2l5,0c1.104,0 2,-0.896 2,-2l0,-46c0,-1.104 -0.896,-2 -2,-2l-5,0c-1.104,0 -2,0.896 -2,2l0,46z" fill="#fff"/>
			<path d="m10.75595,49.71429c0,1.104 0.896,2 2,2l5,0c1.104,0 2,-0.896 2,-2l0,-46c0,-1.104 -0.896,-2 -2,-2l-5,0c-1.104,0 -2,0.896 -2,2l0,46z" fill="none" stroke="#000"/>
			<path class="white" id="v<?php echo $voice; ?>_oct<?php echo $octave; ?>_0" d="m1.75595,49.71429c0,1.104 0.896,2 2,2l5,0c1.104,0 2,-0.896 2,-2l0,-46c0,-1.104 -0.896,-2 -2,-2l-5,0c-1.104,0 -2,0.896 -2,2l0,46z" fill="#fff"/>
			<path d="m1.75595,49.71429c0,1.104 0.896,2 2,2l5,0c1.104,0 2,-0.896 2,-2l0,-46c0,-1.104 -0.896,-2 -2,-2l-5,0c-1.104,0 -2,0.896 -2,2l0,46z" fill="none" stroke="#000"/>
			<rect class="black" id="v<?php echo $voice; ?>_oct<?php echo $octave; ?>_1" height="33" width="4.625" y="1.71429" x="7.71429" stroke-linecap="null" stroke-linejoin="null" stroke-dasharray="null" stroke-width="null" stroke="#000" fill="#000"/>
			<rect class="black" id="v<?php echo $voice; ?>_oct<?php echo $octave; ?>_3" height="33" width="4.625" y="1.75595" x="18.06845" stroke-linecap="null" stroke-linejoin="null" stroke-dasharray="null" stroke-width="null" stroke="#000" fill="#000"/>
			<rect class="black" id="v<?php echo $voice; ?>_oct<?php echo $octave; ?>_6" height="33" width="4.5" y="1.75595" x="34.81845" stroke-linecap="null" stroke-linejoin="null" stroke-dasharray="null" stroke-width="null" stroke="#000" fill="#000"/>
			<rect class="black" id="v<?php echo $voice; ?>_oct<?php echo $octave; ?>_8" height="33" width="4.375" y="1.75595" x="44.56845" stroke-linecap="null" stroke-linejoin="null" stroke-dasharray="null" stroke-width="null" stroke="#000" fill="#000"/>
			<rect class="black" id="v<?php echo $voice; ?>_oct<?php echo $octave; ?>_10" height="33" width="4.5" y="1.75595" x="54.19345" stroke-linecap="null" stroke-linejoin="null" stroke-dasharray="null" stroke-width="null" stroke="#000" fill="#000"/>
			<rect class="filet ff<?php echo $voice; ?>" fill="#000" stroke="#000" stroke-width="null" stroke-dasharray="null" stroke-linejoin="null" stroke-linecap="null" x="1.71429" y="1.71429" width="63.0625" height="1.625"/>
		</svg>
	<?php endfor; ?>
	</div>
<?php endfor; ?>