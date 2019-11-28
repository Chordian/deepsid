# Create CSV file with specific player entries
import csv

if __name__ == '__main__':

	with open('_specific.csv') as f:
		content = f.readlines()

	with open('karl_xii.csv', 'wb') as csvfile:
		writer = csv.writer(csvfile, delimiter=',')

		for line in content:
			if '(Karl_XII' in line:
				writer.writerow(['_High Voltage SID Collection/'+prev_line[0:prev_line.find('.sid') + 4], 'SoundMonitor/'+line[line.find('(') + 1:line.find(')')]])
			prev_line = line