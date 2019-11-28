# Create CSV file with STIL entries
import csv

if __name__ == '__main__':

	with open('stil.txt') as f:
		# Read all lines and strips all \n characters
		content = [line.strip() for line in f.readlines()]

	with open('stil.csv', 'wb') as csvfile:
		writer = csv.writer(csvfile, delimiter=',')

		block_mode = False
		for line in content:
			if not block_mode and line[:1] == '/':
				# A slash in start indicates new SID file STIL block
				block_mode = True
				fullname = line.lstrip('/')
				stil = ''
			elif block_mode:
				if line == '':
					# An empty line after a SID file STIL block indicates the end of it
					block_mode = False
					writer.writerow(['_High Voltage SID Collection/'+fullname, stil[0:-6]]) # Don't use rstrip!
				else:
					# Somewhere inside a SID file STIL block
					stil += line+'<br />'