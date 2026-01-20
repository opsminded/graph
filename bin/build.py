import base64
from pathlib import Path

init = ['<?php\n', '\n', 'declare(strict_types=1);\n', '\n']

printed_files = []

def print_file(output, filename):
    
    global printed_files
    if filename in printed_files:
        return
    
    printed_files.append(filename)

    with open(filename, 'r') as f:
        lines = f.readlines()
        if init[0:4] == lines[0:4]:
            desc = lines[4].strip()
            isclass = desc.startswith('class')
            isfinal = desc.startswith('final')
            isinterface = desc.startswith('interface')
            isabstract = desc.startswith('abstract')
            if isclass or isfinal or isinterface or isabstract:
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

filenames = []
folder = Path('src')
for file in folder.iterdir():
    filenames.append(str(file))


cytoscapejs = ''
with open('www/cytoscape.min.js', 'r') as file:
    cytoscapejs = file.read()
    cytoscapejs = base64.b64encode(cytoscapejs.encode('utf-8')).decode('utf-8')

stylesheet = ''
with open('www/stylesheet.css', 'r') as file:
    stylesheet = file.read()
    stylesheet = base64.b64encode(stylesheet.encode('utf-8')).decode('utf-8')

javascript = ''
with open('www/script.js', 'r') as file:
    javascript = file.read()
    javascript = base64.b64encode(javascript.encode('utf-8')).decode('utf-8')


######################################################

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
    
    # Write cytoscapejs third
    outputfile.write("\n")
    outputfile.write(f"$DATA_CYTOSCAPE = '{str(cytoscapejs)}';\n")
    outputfile.write(f"$DATA_STYLE_CSS = '{str(stylesheet)}';\n")
    outputfile.write(f"$DATA_JAVASCRIPT = '{str(javascript)}';\n")
    outputfile.write("\n")
    
    print_file(outputfile, 'src/HTTPResponse.php')
    for file in folder.iterdir():
        print_file(outputfile, str(file))

printed_files = []
filenames = []
folder = Path('tests')
for file in folder.iterdir():
    filenames.append(str(file))
 
with open('compiled/tests.php', 'w') as outputfile:
    outputfile.write("".join(init))
    
    for file in folder.iterdir():
        print_file(outputfile, str(file))