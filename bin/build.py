import base64
from pathlib import Path

init = ['<?php\n', '\n', 'declare(strict_types=1);\n', '\n']

printed_files = []

def print_file(output, filename):
    
    global printed_files
    if filename in printed_files:
        return
    
    print(f'Printing {filename}')
    printed_files.append(filename)

    with open(filename, 'r') as f:
        lines = f.readlines()
        if init[0:4] == lines[0:4]:
            desc = lines[4].strip()
            isclass = desc.startswith('class')
            isfinal = desc.startswith('final')
            isinterface = desc.startswith('interface')
            isabstract = desc.startswith('abstract')
            iscomment = desc.startswith('//')
            if isclass or isfinal or isinterface or isabstract or iscomment:
                pass
            else:
                raise Exception(desc)
        else:
            print(lines)
            raise Exception('erro na linha')
        
        for l in lines[4:]:
            output.write(l)
    output.write("\n#####################################\n\n")
    
######################################################

compiled_images = ''
with open('compiled/compiled_images.php', 'r') as file:
        compiled_images = file.read()
        

compiled_templates = ''
with open('compiled/compiled_templates.php', 'r') as file:
    compiled_templates = file.read()

compiled_schema = ''
with open('compiled/compiled_schema.php', 'r') as file:
    compiled_schema = file.read()

filenames = []
folder = Path('src')
for file in folder.rglob('*.php'):
    filenames.append(str(file))


with open('compiled/graph.php', 'w') as outputfile:
    
    outputfile.write("".join(init))
    
    # Write compiled images first
    outputfile.write("\n")
    outputfile.write(compiled_images)
    outputfile.write("\n")
    
    # Write compiled templates second
    outputfile.write("\n")
    outputfile.write(compiled_templates)
    outputfile.write("\n")
    
    # Write compiled schema third
    outputfile.write("\n")
    outputfile.write(compiled_schema)
    outputfile.write("\n")
    
    print_file(outputfile, 'src/HTTP/Response/Response.php')
    for file in folder.rglob('*.php'):
        print_file(outputfile, str(file))

printed_files = []
filenames = []
folder = Path('tests')
for file in folder.rglob('*.php'):
    filenames.append(str(file))
 
with open('compiled/tests.php', 'w') as outputfile:
    outputfile.write("".join(init))
    
    for file in folder.rglob('*.php'):
        print_file(outputfile, str(file))