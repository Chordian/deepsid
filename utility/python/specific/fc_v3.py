# Create CSV file with specific player entries
import csv

if __name__ == '__main__':

	with open('_specific.csv', 'r', encoding='utf-8') as f:
		content = f.readlines()

	with open('fc_v3.csv', 'w', newline='', encoding='utf-8') as csvfile:
		writer = csv.writer(csvfile, delimiter=',')

		prev_line = ''
		for line in content:
			if '(FC_V3.x' in line:
				writer.writerow(['_High Voltage SID Collection/'+prev_line[0:prev_line.find('.sid') + 4], 'FutureComposer_V3.x'])
			prev_line = line