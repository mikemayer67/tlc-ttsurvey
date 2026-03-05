from glob import glob
import os
import subprocess

#ttf_files = glob('fonts/ttf/*/*.ttf')
ttf_files = glob('fonts/ttf/**/*.ttf',recursive=True);
variants = ['Regular','Bold','Italic','BoldItalic']
ttf_files = [
    f for f in ttf_files 
    if any( f.endswith(f"-{v}.ttf") for v in variants)
]
for ttf_file in ttf_files:
    cmd = [
        'php',
        'vendor/tecnickcom/tcpdf/tools/tcpdf_addfont.php',
        '-i', ttf_file,
        '-o', 'fonts/tcpdf',
        '-t', 'TrueTypeUnicode',
    ]
    subprocess.run(cmd)
