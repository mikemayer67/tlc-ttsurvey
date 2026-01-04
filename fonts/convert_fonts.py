from glob import glob
import os
import subprocess

ttf_files = glob('fonts/ttf/*/*.ttf')
for ttf_file in ttf_files:
    cmd = [
        'php',
        'vendor/tecnickcom/tcpdf/tools/tcpdf_addfont.php',
        '-i', ttf_file,
        '-o', 'fonts/tcpdf',
        '-t', 'TrueTypeUnicode',
    ]
    subprocess.run(cmd)
