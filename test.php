<?php


    $workbook = new MoodleExcelWorkbook('moodletest.xlsx', 'Excel2007');
    $worksheet = array();
    $worksheet = $workbook->add_worksheet('Supported');
    $worksheet->hide_screen_gridlines();
    $worksheet->write_string(0, 0, 'Moodle worksheet export test', $workbook->add_format(array('color'=>'red', 'size'=>20, 'bold'=>1, 'italic'=>1)));
    $worksheet->set_row(0, 25);
    $worksheet->write(1, 0, 'Moodle release: '.$CFG->release, $workbook->add_format(array('size'=>8, 'italic'=>1)));

    $worksheet->set_column(0, 0, 20);
    $worksheet->set_column(1, 1, 30);
    $worksheet->set_column(2, 2, 5);
    $worksheet->set_column(3, 3, 30);
    $worksheet->set_column(4, 4, 20);

    $miniheading = $workbook->add_format(array('size'=>15, 'bold'=>1, 'italic'=>1, 'underline'=>1));


    $worksheet->write(2, 0, 'Cell types', $miniheading);
    $worksheet->set_row(2, 20);
    $worksheet->set_row(3, 5);

    $worksheet->write(4, 0, 'String');
    $worksheet->write_string(4, 1, 'Žluťoučký koníček');

    $worksheet->write(5, 0, 'Number as string');
    $worksheet->write_string(5, 1, 3.14159);

    $worksheet->write(6, 0, 'Integer');
    $worksheet->write_number(6, 1, 666);

    $worksheet->write(7, 0, 'Float');
    $worksheet->write_number(7, 1, 3.14159);

    $worksheet->write(8, 0, 'URL');
    $worksheet->write_url(8, 1, 'http://moodle.org');

    $worksheet->write(9, 0, 'Date (now)');
    $worksheet->write_date(9, 1, time());

    $worksheet->write(10, 0, 'Formula');
    $worksheet->write(10, 1, '=1+2');

    $worksheet->write(11, 0, 'Blank');
    $worksheet->write_blank(11, 1, $workbook->add_format(array('bg_color'=>'silver')));


    $worksheet->write(14, 0, 'Text formats', $miniheading);
    $worksheet->set_row(14, 20);
    $worksheet->set_row(15, 5);

    // Following writes use alternative format array.
    $worksheet->write(16, 0, 'Bold', array('bold'=>1));
    $worksheet->write(17, 0, 'Italic', array('italic'=>1));
    $worksheet->write(18, 0, 'Single underline', array('underline'=>1));
    $worksheet->write(19, 0, 'Double underline', array('underline'=>2));
    $worksheet->write(20, 0, 'Strikeout', array('strikeout'=>1));
    $worksheet->write(21, 0, 'Superscript', array('script'=>1));
    $worksheet->write(22, 0, 'Subscript', array('script'=>2));
    $worksheet->write(23, 0, 'Red', array('color'=>'red'));


    $worksheet->write(25, 0, 'Text align', $miniheading);

    $workbook->close();

?>