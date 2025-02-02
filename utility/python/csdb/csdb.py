# Create CSV file with CSDb type and ID values - v3
# New v3 rules:
# - Assume SID ID at first
# - If 1 release and "C64 Music", get release ID instead
# - If 2 releases or more and a "C64 Music" exists with exactly the same name as in SID ID entry, get release ID instead

# Idea for harvesting ID's: Loop https://csdb.dk/webservice/?type=sid&id=1 (id 1 to end); find in
# DB via <HVSCPath>; if releases check <Type> for "C64 Music" (exact) and only one entry; if true
# then add the ID for that release entry to DB. End is *slightly* more than HVSC SID count.
# If no "C64 Music" page, consider adding the SID ID itself and let the CSDb tab just show that.
# A lot of SID ID pages are also missing and you have to handle that too in DeepSID.

# https://csdb.dk/webservice/?type=sid&id=0     returns the highest ID! =)

import csv, requests
from xml.etree import ElementTree

if __name__ == '__main__':
    with open('csdb_35000-64400.csv', 'w', newline='', encoding='utf-8') as csvfile:
        writer = csv.writer(csvfile, delimiter=',')

        for sidId in range(35000, 64400):
            response = requests.get(f'https://csdb.dk/webservice/?type=sid&id={sidId}')
            if response.status_code == 200:
                try:
                    tree = ElementTree.fromstring(response.content)
                except ElementTree.ParseError:
                    continue

                sid = tree.find('SID')
                if sid is not None:
                    fullname = sid.find('HVSCPath').text[1:] if sid.find('HVSCPath') is not None else ''
                    sid_id = sid.find('ID').text if sid.find('ID') is not None else ''

                    writer.writerow([f'_High Voltage SID Collection/{fullname}', 'sid', sid_id])