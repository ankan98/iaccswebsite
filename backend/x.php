<?php
require 'mailer.php';

smtp_mailer('sk8327656239@gmail.com', 'Test Subject', '<h1>Test Body</h1>', [], ['pdf-header.png']);