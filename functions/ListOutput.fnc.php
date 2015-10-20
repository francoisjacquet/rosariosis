<?php

function ListOutput($result,$column_names,$singular='.',$plural='.',$link=false,$group=array(),$options=array())
{
	//FJ bugfix ListOutput sorting when more than one list in a page
	$LO_sort = $_REQUEST['LO_sort'];

	if ( !isset($options['save']))
		$options['save'] = '1';
	if ( !isset($options['print']))
		$options['print'] = true;
	if ( !isset($options['search']))
		$options['search'] = true;
	if ( !isset($options['center']))
		$options['center'] = true;
	if ( !isset($options['count']))
		$options['count'] = true;
	if ( !isset($options['sort']))
	{
		//FJ lists with grouping cannot be sorted
		if (empty($group))
			$options['sort'] = true;
		else
		{
			$options['sort'] = false;

			unset($LO_sort);
		}
	}
	/*if ( !isset($options['cellpadding']))
		$options['cellpadding'] = '6';*/
	if ( !isset($options['header_color']))
		$options['header_color'] = Preferences('HEADER');
	//FJ add responsive table option
	//note: should be set to false when the list table have cell content that occupies more than one line height, like the Portal Notes'
	if ( !isset($options['responsive']))
		$options['responsive'] = true;

	if ( !$link)
		$link = array();

	if ( !isset($options['add']))
	{
		if ( !AllowEdit() || isset($_REQUEST['_ROSARIO_PDF']))
		{
			if ( $link)
			{
				unset($link['add']);
				unset($link['remove']);
			}
		}
	}

	// PREPARE LINKS ---
	$result_count = $display_count = count($result);
	$num_displayed = 100000;
	$extra = 'LO_page='.(isset($_REQUEST['LO_page'])?$_REQUEST['LO_page']:'').'&amp;LO_sort='.(isset($LO_sort)?$LO_sort:'').'&amp;LO_direction='.(isset($_REQUEST['LO_direction'])?$_REQUEST['LO_direction']:'').'&amp;LO_search='.(isset($_REQUEST['LO_search'])?urlencode($_REQUEST['LO_search']):'');

	$PHP_tmp_SELF = PreparePHP_SELF($_REQUEST,array('LO_page','LO_sort','LO_direction','LO_search','LO_save','remove_prompt','remove_name'));

	// END PREPARE LINKS ---

	// UN-GROUPING
	if (empty($group))
		$group_count = false;
	else
		$group_count = count($group);

	if ( $group_count && $result_count)
	{
		$group_result = $result;
		unset($result);
		$result[0] = '';

		foreach ( (array)$group_result as $item1)
		{
			$i=0;
			foreach ( (array)$item1 as $item2)
			{
				if ( $group_count==1)
				{
					$i++;
					if (count($group[0]) && $i!=1)
					{
//FJ fix error Warning: Invalid argument supplied for foreach()
//						foreach ( (array)$group[0] as $column)
						$group[0]=$column;
							$item2[$column] = str_replace('<!-- <!--','<!--','<!-- '.str_replace('-->','--><!--',$item2[$column])).' -->&nbsp;';
					}
					$result[] = $item2;
				}
				else
				{
					foreach ( (array)$item2 as $item3)
					{
						if ( $group_count==2)
						{
							$i++;
							if (count($group[0]) && $i!=1)
							{
//FJ fix error Warning: Invalid argument supplied for foreach()
		//						foreach ( (array)$group[0] as $column)
								$group[0]=$column;
									$item3[$column] = '<!-- '.$item3[$column].' -->';
							}
							if (count($group[1]) && $i!=1)
							{
//FJ fix error Warning: Invalid argument supplied for foreach()
//								foreach ( (array)$group[1] as $column)
								$group[1]=$column;
									$item3[$column] = '<!-- '.$item3[$column].' -->';
							}
							//$item3['row_color'] = $color;
							$result[] = $item3;
						}
						else
						{
							foreach ( (array)$item3 as $item4)
							{
								if ( $group_count==3)
								{
									$i++;
									if (count($group[2]) && $i!=1)
									{
//FJ fix error Warning: Invalid argument supplied for foreach()
//										foreach ( (array)$group[2] as $column)
										$group[2]=$column;
											unset($item4[$column]);
									}
									//$item4['row_color'] = $color;
									$result[] = $item4;
								}
							}
						}
					}
				}
			}
			$i = 0;
		}
		unset($result[0]);
		$result_count = count($result);
	}
	// END UN-GROUPING
	$_LIST['output'] = true;


	// PRINT HEADINGS, PREPARE PDF, AND SORT THE LIST ---
	if ( $_LIST['output']!=false)
	{
		if ( $result_count != 0)
		{
			$count = 0;
			if (isset($link['remove']['variables']))
				$remove = count($link['remove']['variables']);
			else
				$remove = 0;
			$cols = count($column_names);

			// HANDLE SEARCHES ---
//FJ fix bug search when only saving
//			if ( $result_count && $_REQUEST['LO_search'] && $_REQUEST['LO_search']!='Search')
			if ( $result_count && !empty($_REQUEST['LO_search']))
			{
				//$_REQUEST['LO_search'] = $search_term = str_replace('\\\"','"',$_REQUEST['LO_search']);
				//$_REQUEST['LO_search'] = $search_term = preg_replace('/[^a-zA-Z0-9 _"]*/','',mb_strtolower($search_term));
				$search_term = mb_strtolower(str_replace("''", "'", $_REQUEST['LO_search']));
				
				if (mb_substr($search_term,0,1)!='"' && mb_substr($search_term,-1,1)!='"')
				{
					$search_term = str_replace('"','',$search_term);
					while ( $space_pos = mb_strpos($search_term,' '))
					{
						$terms[mb_substr($search_term,0,$space_pos)] = 1;
						$search_term = mb_substr($search_term,($space_pos+1));
					}
					$terms[trim($search_term)] = 1;
				}
				else
				{
					$search_term = str_replace('"','',$search_term);
					$terms[trim($search_term)] = 1;
				}

				/* TRANSLATORS: List of words ignored during search operations */
				$ignored_words = explode(',',_('of, the, a, an, in'));

				foreach ($ignored_words as $word)
					unset($terms[trim($word)]);

				foreach ( (array)$result as $key => $value)
				{
					$values[$key] = 0;
					foreach ( (array)$value as $val)
					{
						//FJ better list searching by isolating the values
						//$val = preg_replace('/[^a-zA-Z0-9 _]+/','',mb_strtolower($val));
						$val = mb_strtolower(strip_tags(preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', "", $val)));

						//if (mb_strtolower($_REQUEST['LO_search'])==$val)
						if ( $search_term==$val)
							$values[$key] += 25;

						foreach ( (array)$terms as $term => $one)
						{
							if (mb_strpos($val,$term)!==FALSE)
								$values[$key] += 3;
						}
					}
					if ( $values[$key]==0)
					{
						unset($values[$key]);
						unset($result[$key]);
						$result_count--;
						$display_count--;
					}
				}
				if ( $result_count)
				{
					array_multisort($values,SORT_DESC,$result);
					$result = _ReindexResults($result);
					$values = _ReindexResults($values);

					$last_value = 1;
					$scale = (100/$values[$last_value]);

					for ( $i=$last_value;$i<=$result_count;$i++)
						$result[$i]['RELEVANCE'] = '<!--' . ((int) ($values[$i]*$scale)) . '--><div class="bar relevance" style="width:'.((int) ($values[$i]*$scale)).'px;">&nbsp;</div>';
				}
				$column_names['RELEVANCE'] = _('Relevance');

				if (is_array($group) && count($group))
				{
					$options['count'] == false;
					$display_zero = true;
				}
			}

			// END SEARCHES ---

			if ( !empty($LO_sort))
			{
				foreach ( (array)$result as $sort)
				{
					if (mb_substr($sort[$LO_sort],0,4)!='<!--')
						//FJ better list sorting by isolating the values
						//$sort_array[] = $sort[$LO_sort];
						$sort_array[] = strip_tags(preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', "", $sort[$LO_sort]));
					else
						$sort_array[] = mb_substr($sort[$LO_sort],4,mb_strpos($sort[$LO_sort],'-->')-5);
				}

				if ( $_REQUEST['LO_direction']==-1)
					$dir = SORT_DESC;
				else
					$dir = SORT_ASC;

				if ( $result_count>1)
				{
					if (is_int($sort_array[1]) || is_double($sort_array[1]))
						array_multisort($sort_array,$dir,SORT_NUMERIC,$result);
					else
						array_multisort($sort_array,$dir,$result);
					for ( $i=$result_count-1;$i>=0;$i--)
						$result[$i+1] = $result[$i];
					unset($result[0]);
				}
			}
		}

		// HANDLE SAVING THE LIST ---
		if ( $options['save'] && $_REQUEST['LO_save']==$options['save'])
		{
			if ( !$options['save_delimiter'] && Preferences('DELIMITER')=='CSV')
				$options['save_delimiter'] = 'comma';
			switch ( $options['save_delimiter'])
			{
				case 'comma':
					$extension = 'csv';
				break;
				case 'xml':
					$extension = 'xml';
				break;
				default:
					$extension = 'xls';
				break;
			}
			ob_end_clean();
			if ( $options['save_delimiter']!='xml')
			{
				foreach ( (array)$column_names as $key => $value)
				{
					$value = ParseMLField($value);
					if ( $options['save_delimiter']=='comma' && !$options['save_quotes'])
						$value = str_replace(',',';',$value);
					$output .= ($options['save_quotes']?'"':'') . str_ireplace('&nbsp;',' ',str_ireplace('<BR />',' ',preg_replace('/<!--.*-->/','',$value))) . ($options['save_quotes']?'"':'') . ($options['save_delimiter']=='comma'?',':"\t");
				}
				$output .= "\n";
			}
			foreach ( (array)$result as $item)
			{
				foreach ( (array)$column_names as $key => $value)
				{
					$value = $item[$key];
					if ( $options['save_delimiter']=='comma' && !$options['save_quotes'])
						$value = str_replace(',',';',$value);
					$value = preg_replace('!<SELECT.*SELECTED\>([^<]+)<.*</SELECT\>!i','\\1',$value);
					$value = preg_replace('!<SELECT.*</SELECT\>!i','',$value);
					$output .= ($options['save_quotes']?'"':'') . ($options['save_delimiter']=='xml'?'<'.str_replace(' ','',$value).'>':'') . trim(str_replace('  ',' ',preg_replace('/<[^>]+>/',' ',preg_replace("/<div onclick='[^']+'>/",'',preg_replace('/ +/',' ',preg_replace('/&[^;]+;/','',str_replace("\r",'',str_replace("\n",'',str_ireplace('<BR />',' ',str_ireplace('<BR />&middot;',' : ',str_ireplace('&nbsp;',' ',$value))))))))))) . ($options['save_delimiter']=='xml'?'</'.str_replace(' ','',$value).'>'."\n":'') . ($options['save_quotes']?'"':'') . ($options['save_delimiter']=='comma'?',':"\t");
				}
				$output .= "\n";
			}
//FJ accents problem + Arabic chars
//http://stackoverflow.com/questions/6002256/is-it-possible-to-force-excel-recognize-utf-8-csv-files-automatically
			if ( $extension == 'xls') //convert to for Excel only, CSV in UTF8
				$output = utf8_decode($output);

			header("Cache-Control: public");
			header("Content-Type: application/$extension");
			header("Content-Length: " . strlen($output));
			header("Content-Disposition: inline; filename=\"".ProgramTitle().".$extension\"\n");

			echo $output;
			exit();
		}
		// END SAVING THE LIST ---

		if (($options['count'] || $display_zero) && ((($result_count==0 || $display_count==0) && $plural) || ($result_count==0 || $display_count==0)))
		{
			echo '<TABLE class="';

			if (isset($_REQUEST['_ROSARIO_PDF']))
				echo ' width-100p';

			if ( $options['center'])
				echo ' center';

			echo '"><TR><TD class="center">';
		}

		if ( $options['count'] || $display_zero)
		{
			if ( $result_count==0 || $display_count==0)
			{
//FJ fix bug ngettext when the plural form is not registered as this in the rosario.po file
//                echo "<b>".sprintf(_('No %s were found.'),ngettext($singular, $plural, 0))."</b> &nbsp; &nbsp;";
				$singular_message = ngettext($singular, $plural, 0);
				if ( $singular_message == $singular)
				{
					$singular_message = _($singular);
				}
                echo '<b>'.sprintf(_('No %s were found.'),$singular_message).'</b> &nbsp; &nbsp;';
			}
		}

		if ( $result_count!=0 || !empty($_REQUEST['LO_search']))
		{
			if ( !isset($_REQUEST['_ROSARIO_PDF']))
			{
				if (empty($_REQUEST['LO_page']))
					$_REQUEST['LO_page'] = 1;

				if ( $_REQUEST['LO_page'] < 1) //FJ check LO_page
					$_REQUEST['LO_page'] = 1;

				if (empty($_REQUEST['LO_direction']))
					$_REQUEST['LO_direction'] = 1;

				$start = ($_REQUEST['LO_page'] - 1) * $num_displayed + 1;
				$stop = $start + ($num_displayed-1);

				if ( $stop > $result_count)
					$stop = $result_count;

				/*if ( $result_count > $num_displayed)
				{
					$where_message = "".sprintf(_('Displaying %d through %d'),$start,$stop)."";
					if (ceil($result_count/$num_displayed) <= 10)
					{	
						$ceil = ceil($result_count/$num_displayed);
						for ( $i=1;$i<=$ceil;$i++)
						{
							if ( $i!=$_REQUEST['LO_page'])
								$LO_pages .= '<A HREF="'.$PHP_tmp_SELF.'&amp;LO_sort='.$LO_sort.'&amp;LO_direction='.$_REQUEST['LO_direction'].'&amp;LO_search='.urlencode($_REQUEST['LO_search']).'&amp;LO_page='.$i.'">'.$i.'</A>, ';
							else
								$LO_pages .= $i.', ';
						}
						$LO_pages = mb_substr($LO_pages,0,-2) . "<BR />";
					}
					else
					{
						for ( $i=1;$i<=7;$i++)
						{
							if ( $i!=$_REQUEST['LO_page'])
								$LO_pages .= '<A HREF="'.$PHP_tmp_SELF.'&amp;LO_sort='.$LO_sort.'&amp;LO_direction='.$_REQUEST['LO_direction'].'&amp;LO_search='.urlencode($_REQUEST['LO_search']).'&amp;LO_page='.$i.'">'.$i.'</A>, ';
							else
								$LO_pages .= $i.', ';
						}
						$LO_pages = mb_substr($LO_pages,0,-2) . " ... ";
						$ceil = ceil($result_count/$num_displayed);
						for ( $i=$ceil-2;$i<=$ceil;$i++)
						{
							if ( $i!=$_REQUEST['LO_page'])
								$LO_pages .= '<A HREF="'.$PHP_tmp_SELF.'&amp;LO_sort='.$LO_sort.'&amp;LO_direction='.$_REQUEST['LO_direction'].'&amp;LO_search='.urlencode($_REQUEST['LO_search']).'&amp;LO_page='.$i.'">'.$i.'</A>, ';
							else
								$LO_pages .= $i.', ';
						}
						$LO_pages = mb_substr($LO_pages,0,-2) . ' &nbsp;<A HREF="'.$PHP_tmp_SELF.'&amp;LO_sort='.$LO_sort.'&amp;LO_direction='.$_REQUEST['LO_direction'].'&amp;LO_search='.urlencode($_REQUEST['LO_search']).'&amp;LO_page=' . ($_REQUEST['LO_page'] +1) . '">'._('Next LO_page').'</A><BR />';
					}
					echo sprintf(_('Go to LO_page %s'),$LO_pages);
					echo '</TD></TR></TABLE>';
					echo '<BR />';
				}*/
			}
			else
			{
				$start = 1;
				$stop = $result_count;
				if ( $cols>8 || $_REQUEST['expanded_view'])
				{
					//FJ wkhtmltopdf
					$_SESSION['orientation'] = 'landscape';
				}

				if ( $options['print'])
				{
//FJ bug PDF
/*					$html = explode('<div style="page-break-after: always;"></div>',mb_strtolower(ob_get_contents()));
					$html = $html[count($html)-1];
					echo '</TD></TR></TABLE>';
					$br = (mb_substr_count($html,'<BR />')) + (mb_substr_count($html,'</p>')) + (mb_substr_count($html,'</tr>')) + (mb_substr_count($html,'</h1>')) + (mb_substr_count($html,'</h2>')) + (mb_substr_count($html,'</h3>')) + (mb_substr_count($html,'</h4>')) + (mb_substr_count($html,'</h5>'));
					if ( $br%2!=0)
					{
						$br++;
						echo '<BR />';
					}*/
				}
				else
					echo '</TD></TR></TABLE>';
			}
			// END MISC ---

			// SEARCH BOX & MORE HEADERS
			if ( !empty($options['header']))
				echo '<TABLE class="postbox width-100p cellspacing-0" style="margin-bottom:0px; border-bottom:solid 1px #f1f1f1;"><TR><TD class="center">'.$options['header'].'</TD></TR></TABLE><div class="postbox" style="padding:5px; border-top:none; border-top-left-radius:0px; border-top-right-radius:0px; box-shadow: none;">';
				
			if ( !empty($where_message) || (($singular!='.') && ($plural!='.')) || (!isset($_REQUEST['_ROSARIO_PDF']) && $options['search']))
			{
				echo '<TABLE class="width-100p">';
				echo '<TR class="st"><TD>';
				if (($singular!='.') && ($plural!='.') && $options['count'])
				{
					if ( $display_count>0)
					{
//FJ fix bug ngettext when the plural form is not registered as this in the rosario.po file
//						echo "<b>".sprintf(ngettext('%d %s was found.','%d %s were found.', $display_count), $display_count, ngettext($singular, $plural, $display_count))."</b> &nbsp; &nbsp;";
						$plural_message = ngettext($singular, $plural, $display_count);
						if (($plural_message == $plural || ($plural_message == _($singular) && $display_count!=1)) && _($plural)!=$plural)
						{
							$plural_message = _($plural);
							if ( $display_count==1) 
								$plural_message = _($singular);
						}
						echo '<b>'.sprintf(ngettext('%d %s was found.','%d %s were found.', $display_count), $display_count, $plural_message).'</b>&nbsp;&nbsp;';
					}
					if ( !empty($where_message))
						echo '<BR />'.$where_message;
				}

				if ( $options['save'] && !isset($_REQUEST['_ROSARIO_PDF']) && $result_count>0)
					echo '<A HREF="'.$PHP_tmp_SELF.'&amp;'.$extra.'&amp;LO_save='.$options['save'].'&amp;_ROSARIO_PDF=true" target="_blank"><IMG SRC="assets/themes/'. Preferences('THEME') .'/btn/download.png" class="alignImg" title="'._('Export list').'" /></A>';

				echo '</TD>';
				$colspan = 1;
				if ( !isset($_REQUEST['_ROSARIO_PDF']) && $options['search'])
				{
					echo '<TD style="text-align:right">';

					$search_URL = PreparePHP_SELF( $_REQUEST, array( 'LO_search', 'LO_page' ) ) . '&LO_search=';

					echo '<INPUT type="text" id="LO_search" name="LO_search" value="'.htmlspecialchars($_REQUEST['LO_search'],ENT_QUOTES).'" placeholder="'._('Search').'" onkeypress="LOSearch(event, this.value, \'' . $search_URL . '\');" /><INPUT type="button" value="'._('Go').'" onclick="LOSearch(false, document.getElementById(\'LO_search\').value, \'' . $search_URL . '\');" /></TD>';
					$colspan++;
				}

				echo '</TR></TABLE>';
			}

			echo '<div style="overflow-x:auto;"><TABLE class="list widefat width-100p cellspacing-0 '.($options['responsive'] && !isset($_REQUEST['_ROSARIO_PDF']) ? 'rt' : '').'">';
			echo '<THEAD><TR>';

			$i = 1;
			if ( $remove && !isset($_REQUEST['_ROSARIO_PDF']) && $result_count!=0)
			{
				echo '<TH>&nbsp;</TH>';
				$i++;
			}

			if ( $result_count!=0 && $cols)
			{
				foreach ( (array)$column_names as $key => $value)
				{
					if (isset($LO_sort) && $LO_sort==$key)
						$direction = -1 * $_REQUEST['LO_direction'];
					else
						$direction = 1;

					if (isset($_REQUEST['_ROSARIO_PDF']))
					{
						echo '<TD style="background-color:'.$options['header_color'].'; color:#fff;"><b>';
						echo ParseMLField($value);
						echo '</b></TD>';
					}
					else
					{
						echo '<TH>';

						if ( $options['sort'] )
							echo '<A HREF="'.$PHP_tmp_SELF.'&amp;LO_page='.$_REQUEST['LO_page'].'&amp;LO_sort='.$key.'&amp;LO_direction='.$direction.'&amp;LO_search='.urlencode(isset($_REQUEST['LO_search'])?$_REQUEST['LO_search']:'') . '">' .
								ParseMLField( $value ) .
							'</A>';
						else
							echo ParseMLField( $value );

						echo '</TH>';
					}
					$i++;
				}
			}

			echo '</TR></THEAD><TBODY>';

			// mab - enable add link as first or last
			if ( $result_count!=0 && isset($link['add']['first']) && ($stop-$start+1)>=$link['add']['first'])
			{
				if ( $link['add']['link'] && !isset($_REQUEST['_ROSARIO_PDF']))
					echo '<TR><TD colspan="'.($remove?$cols+1:$cols).'">'.button('add',$link['add']['title'],$link['add']['link']).'</TD></TR>';
				elseif ( $link['add']['span'] && !isset($_REQUEST['_ROSARIO_PDF']))
					echo '<TR><TD colspan="'.($remove?$cols+1:$cols).'">'.button('add').$link['add']['span'].'</TD></TR>';
				elseif ( $link['add']['html'] && $cols)
				{
					echo '<TR>';
					if ( $remove && !isset($_REQUEST['_ROSARIO_PDF']) && $link['add']['html']['remove'])
						echo '<TD>'.$link['add']['html']['remove'].'</TD>';
					elseif ( $remove && !isset($_REQUEST['_ROSARIO_PDF']))
						echo '<TD>'.button('add').'</TD>';

					foreach ( (array)$column_names as $key => $value)
					{
						echo '<TD>'.$link['add']['html'][$key].'</TD>';
					}
					echo '</TR>';
					$count++;
				}
			}

			for ( $i=$start;$i<=$stop;$i++)
			{
				$item = $result[$i];
				if (isset($_REQUEST['_ROSARIO_PDF']) && $options['print'] && count($item))
				{
					//modify loop: use for instead of foreach
					$key = array_keys($item);
					$size = sizeOf($key);
					for ($j=0; $j<$size; $j++)
					{
						$value = preg_replace('!<SELECT.*SELECTED\>([^<]+)<.*</SELECT\>!i','\\1',$item[$key[$j]]);
						$value = preg_replace('!<SELECT.*</SELECT\>!i','',$value);
						$item[$key[$j]] = preg_replace("/<div onclick=[^']+'>/",'',$value);
					}
					
					/*foreach ( (array)$item as $key => $value)
					{
						$value = preg_replace('!<SELECT.*SELECTED\>([^<]+)<.*</SELECT\>!i','\\1',$value);
						$value = preg_replace('!<SELECT.*</SELECT\>!i','',$value);

						$item[$key] = preg_replace("/<div onclick=[^']+'>/",'',$value);
					}*/
				}

				if ( !empty($item['row_color']))
					$color = $item['row_color'];
				else
					$color = '';

				echo '<TR>';
				$count++;

				if ( $remove && !isset($_REQUEST['_ROSARIO_PDF']))
				{
					$button_title = $link['remove']['title'];
					$button_link = $link['remove']['link'];
					if (count($link['remove']['variables']))
					{
						foreach ( (array)$link['remove']['variables'] as $var => $val)
							$button_link .= "&$var=" . urlencode($item[$val]);
					}

					echo '<TD>' . button('remove',$button_title,'"'.$button_link.'"') . '</TD>';
				}

				if ( $cols)
				{
					foreach ( (array)$column_names as $key => $value)
					{
						if ( !empty($link[$key]) && $item[$key]!==false && !isset($_REQUEST['_ROSARIO_PDF']))
						{
							if ( $color==Preferences('HIGHLIGHT'))
								echo '<TD class="highlight">';
							else
								echo '<TD>';
							if ( !empty($link[$key]['js']))
							{
								echo '<A HREF="#" onclick=\'window.open("'.$link[$key]['link'];
								if (count($link[$key]['variables']))
								{
									foreach ( (array)$link[$key]['variables'] as $var => $val)
										echo "&$var=".urlencode($item[$val]);
								}
								echo '","","scrollbars=yes,resizable=yes,width=800,height=400");\'';
								if ( $link[$key]['extra'])
									echo ' '.$link[$key]['extra'];
								echo '>';
							}
							else
							{
								echo '<A HREF="'.$link[$key]['link'];
								if (count($link[$key]['variables']))
								{
									foreach ( (array)$link[$key]['variables'] as $var => $val)
										echo '&'.$var.'='.urlencode($item[$val]);
								}
								echo '"';
								if ( !empty($link[$key]['extra']))
									echo ' '.$link[$key]['extra'];
								echo '>';
							}
							echo $item[$key];
							if ( !$item[$key])
								echo '***';
							echo '</A>';
							echo '</TD>';
						}
						else
						{
							if ( $color==Preferences('HIGHLIGHT'))
								echo '<TD class="highlight">';
							else
								echo '<TD>';
							echo $item[$key];
							if ( !$item[$key])
								echo '&nbsp;';
							echo '</TD>';
						}
					}
				}
				echo '</TR>';
			}

			if ( $result_count!=0 && (!isset($link['add']['first']) || ($stop-$start+1)<$link['add']['first']))
			{
				//if ( $remove && !isset($_REQUEST['_ROSARIO_PDF']))
				//	$cols++;
				if (isset($link['add']['link']) && !isset($_REQUEST['_ROSARIO_PDF']))
					echo '<TR><TD colspan="'.($remove?$cols+1:$cols).'">'.button('add',$link['add']['title'],$link['add']['link']).'</TD></TR>';
				elseif (isset($link['add']['span']) && !isset($_REQUEST['_ROSARIO_PDF']))
					echo '<TR><TD colspan="'.($remove?$cols+1:$cols).'">'.button('add').$link['add']['span'].'</TD></TR>';
				elseif (isset($link['add']['html']) && $cols)
				{
					echo '<TR>';
					if ( $remove && !isset($_REQUEST['_ROSARIO_PDF']) && $link['add']['html']['remove'])
						echo '<TD>'.$link['add']['html']['remove'].'</TD>';
					elseif ( $remove && !isset($_REQUEST['_ROSARIO_PDF']))
						echo '<TD>'.button('add').'</TD>';

					foreach ( (array)$column_names as $key => $value)
					{
						echo '<TD>'.$link['add']['html'][$key].'</TD>';
					}
					echo '</TR>';
				}
			}
			if ( $result_count!=0)
			{
				echo '</TBODY></TABLE></div><BR />';
			}
			if ( !empty($options['header']))
				echo '</div>';

		// END PRINT THE LIST ---
		}
		if ( $result_count==0)
		{
			// mab - problem with table closing if not opened above - do same conditional?
			if (($options['count'] || $display_zero) && ((($result_count==0 || $display_count==0) && $plural) || ($result_count==0 || $display_count==0)))
				echo '</TD></TR></TBODY></TABLE>';
				
			if ( !empty($options['header']))
				echo '<TABLE class="postbox width-100p cellspacing-0" style="margin-bottom:0px; border-bottom:0px;"><TR><TD class="center">'.$options['header'].'</TD></TR></TABLE><div class="postbox" style="padding:5px; border-top:none; border-top-left-radius:0px; border-top-right-radius:0px; box-shadow: none;">';

			if ( $link['add']['link'] && !isset($_REQUEST['_ROSARIO_PDF']))
				echo '<div class="center">' . button('add',$link['add']['title'],$link['add']['link']) . '</div>';
			elseif (($link['add']['html'] || $link['add']['span']) && count($column_names) && !isset($_REQUEST['_ROSARIO_PDF']))
			{
				// WIDTH=100%
				if ( $link['add']['html'])
				{
					echo '<div style="overflow-x:auto;"><TABLE class="widefat width-100p cellspacing-0';			
					if ( $options['responsive'] && !isset($_REQUEST['_ROSARIO_PDF']))
						echo ' rt';

					if ( $options['center'])
						echo ' center';

					echo '"><THEAD><TR><TH>&nbsp;</TH>';

					foreach ( (array)$column_names as $key => $value)
					{
						echo '<TH>' . str_replace(' ','&nbsp;',$value) . '</TH>';
					}
					echo '</TR></THEAD>';

					echo '<TBODY><TR>';

					if ( $link['add']['html']['remove'])
						echo '<TD>'.$link['add']['html']['remove'].'</TD>';
					else
						echo '<TD>'.button('add').'</TD>';

					foreach ( (array)$column_names as $key => $value)
					{
						echo '<TD>'.$link['add']['html'][$key].'</TD>';
					}
					echo '</TR></TBODY>';
					echo '</TABLE></div><BR />';
				}
				elseif ( $link['add']['span'] && !isset($_REQUEST['_ROSARIO_PDF']))
				{
					echo '<TABLE class="postbox';

					if ( $options['center'])
						echo ' center';

					echo '"><TR><TD>'.button('add').$link['add']['span'].'</TD></TR></TABLE>';
				}
			}
			if ( !empty($options['header']))
				echo '</div>';
		}
	}
}

//FJ function moved from functions/ to here because used only in this file
function _ReindexResults($array)
{
 	$i=1;
	foreach ( (array)$array as $value)
	{
		$new[$i]=$value;
		$i++;
	}
	return $new;
}
