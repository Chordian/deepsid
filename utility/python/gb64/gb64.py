# - previousSID = ''
# 
# - Loop CSV line by line
# 
#     - Read SID col, if SID col == previousSID
#         - If not "[Preview]" type = "RELEASE"
# 
#         - Array += GBName() and GB ID
# 
#     - Else
#         // Finish batch
#         If previousSID !== '':
#           - Save CSV line to new file with:
#             fullname (from previousSID value), type, GB Array serialized (WP style)
# 
#         // Start new batch
#         - previousSID = SID col
#         - If name has "[Preview]" in it: type = "PREVIEW"
#           Else type = "RELEASE"
# 
#         - Start Array = GBName() and GB ID (first entry)
# 
# GBName():
#     -  name = name with ", A" and ", The" moved to the front instead
#     -  Return name

import csv
from phpserialize import *

def adaptName(name):
	name = name.rstrip()
	end = name.split(',')[-1]
	if end == ' The':
		name = 'The '+name[:-5]
	elif end == ' Der':
		name = 'Der '+name[:-5]
	elif end == ' Das':
		name = 'Das '+name[:-5]
	elif end == ' Des':
		name = 'Des '+name[:-5]
	elif end == ' Die':
		name = 'Die '+name[:-5]
	elif end == ' Het':
		name = 'Het '+name[:-5]
	elif end == ' A':
		name = 'A '+name[:-3]
	elif end == ' I':
		name = 'I '+name[:-3]
	elif end == ' The [Preview]':
		name = 'The '+name[:-15]+' [Preview]'
	elif end == ' A [Preview]':
		name = 'A '+name[:-13]+' [Preview]'
	elif end == ' A [The Revenge]':
		name = 'A '+name[:-17]+' [The Revenge]'
	elif end == ' De':
		name = 'De '+name[:-4]
	elif end == ' El':
		name = 'El '+name[:-4]
	elif end == ' Il':
		name = 'Il '+name[:-4]
	elif end == ' La':
		name = 'La '+name[:-4]
	elif end == ' Le':
		name = 'Le '+name[:-4]
	elif end == " L'":
		name = "L'"+name[:-4]
	elif end == ' Eine':
		name = 'Eine '+name[:-6]

	if ", The - " in name:
		name = 'The '+name.replace(', The - ', ' - ')
	elif ", Die - " in name:
		name = 'Die '+name.replace(', Die - ', ' - ')
	
	return name

if __name__ == '__main__':

	with open('gbsource.csv') as f:
		content = f.readlines()

	with open('gbdest.csv', 'wb') as csvfile:
		writer = csv.writer(csvfile, delimiter = ';')

		previousSID = ''
		for line in content:

			fullname, id, name = line.split(';')

			if fullname == previousSID:
				# Add to batch
				if '[preview]' not in name.lower():
					type = 'RELEASE'

				gamebase.append({
					"name":		adaptName(name),
					"id":		id
				})

			else:
				# Finish batch
				if previousSID != '':
					writer.writerow(['_High Voltage SID Collection/'+previousSID, type, dumps(gamebase)])

				# Start new batch
				previousSID = fullname
				if '[preview]' in name.lower():
					type = 'PREVIEW'
				else:
					type = 'RELEASE'

				gamebase = [{
					"name":		adaptName(name),
					"id":		id
				}]
