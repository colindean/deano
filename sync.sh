#/bin/bash

bzr push
ssh cad.cx "rm -rf ~/www/deano.cad.cx/*; bzr export ~/www/deano.cad.cx/ ~/Source/repos/deano-microframework"
