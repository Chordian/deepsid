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

	with open('csdb_00001-1999.csv', 'wb') as csvfile:
		writer = csv.writer(csvfile, delimiter=',')

		for sidId in range(00001, 1999):
			response = requests.get('https://csdb.dk/webservice/?type=sid&id=%s' % sidId)
			if response.status_code == 200:
				try:
					tree = ElementTree.fromstring(response.content)
				except:
					# print 'Skipped ID %s' % sidId
					continue

				sid = tree.find('SID')
				fullname = sid.find('HVSCPath').text[1:]
				#name = sid.find('Name').text.lower()

				type = 'sid'
				id = sid.find('ID').text

				# The code for 'C64 Music' has been disabled as releases for this is no longer needed.
				# The site has been changed to instead show the release immediately if it's the only one,
				# regardless of what type it is. This works much better.

				#usedin = sid.find('UsedIn')
				#if usedin != None:
				#	if len(list(usedin)) == 1:
				#		# One release; if type is 'C64 Music' just get it
				#		release = usedin.find('Release')
				#		if release.find('Type').text == 'C64 Music':
				#			type = 'release'
				#			id = release.find('ID').text
				#	else:
				#		# Multiple releases; type 'C64 Music' much match name before we accept it
				#		releases = usedin.findall('Release')
				#		for release in releases:
				#			if release.find('Type').text == 'C64 Music' and release.find('Name').text.lower() == name:
				#				type = 'release'
				#				id = release.find('ID').text
				#				break

				#print '\nHVSC path: %s' % fullname
				#print 'Type: %s, ID: %s' % (type, id)

				writer.writerow(['_High Voltage SID Collection/'+fullname, type, id])
