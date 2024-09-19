#!/bin/bash
PLAYLIST=("JOLJ3_Addams_Family_Trio_Pumpkin" "JOLJ3_Ghostbusters_Trio_Pumpkin" "JOLJ3_Monster_Mash_Trio_Pumpkin")

for i in "${PLAYLIST[@]}"
do
    FILENAME="$i.mp4"
    echo $FILENAME

    mplayer -fs $FILENAME
    mplayer -fs JOLJ3_Buffer_Trio_Pumpkin.mp4
done

