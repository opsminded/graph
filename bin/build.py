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

filenames = []
folder = Path('src')
for file in folder.iterdir():
    filenames.append(str(file))


######################################################

with open('compiled/graph.php', 'w') as outputfile:
    outputfile.write("".join(init))
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